<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Intern;
use App\Models\InternBatch;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses an uploaded intern name list — Excel (.xlsx/.csv) or Word (.docx)
 * — into Intern rows for a batch.
 *
 * Excel: uses PhpSpreadsheet, the same library already used by
 * EmployeeImportProcessor — a confirmed-available dependency, not a new one.
 *
 * Word (.docx): DELIBERATELY does not add phpoffice/phpword as a new
 * dependency, since its availability can't be verified without a
 * composer.json in this project (see the same reasoning that led to using
 * raw SQL instead of ->change() in an earlier migration). A .docx file IS
 * a zip archive containing word/document.xml — this reads that XML
 * directly with PHP's built-in ZipArchive and DOMDocument, both part of
 * PHP core, adding zero new dependencies. One intern name per paragraph
 * is assumed — a plain list, not a table (a .docx table would need
 * different XML traversal; if the source file has a table instead of a
 * plain list, export it as .xlsx instead, which is more reliable anyway).
 */
class InternListImportService
{
    /** @return array{imported:int, skipped_blank:int, names:array<int,string>} */
    public static function import(string $filePath, string $extension, InternBatch $batch): array
    {
        $names = match (strtolower($extension)) {
            'xlsx', 'xls', 'csv' => self::extractFromSpreadsheet($filePath),
            'docx' => self::extractFromWord($filePath),
            default => throw new \InvalidArgumentException("Unsupported file type: {$extension}. Use .xlsx, .csv, or .docx."),
        };

        $imported = 0;
        $skippedBlank = 0;
        foreach ($names as $name) {
            // Collapse embedded newlines/tabs/multiple spaces (e.g. from a
            // messy multi-line cell) into single spaces before trimming.
            $name = preg_replace('/\s+/', ' ', $name);
            $name = trim($name);
            if ($name === '') {
                $skippedBlank++;

                continue;
            }
            // Idempotent per batch. The name itself is encrypted, so lookup must
            // use the deterministic HMAC blind index rather than comparing
            // plaintext against ciphertext.
            $existing = Intern::query()
                ->where('intern_batch_id', $batch->id)
                ->wherePiiEquals('name', $name)
                ->first();

            if (! $existing) {
                Intern::create([
                    'intern_batch_id' => $batch->id,
                    'name' => $name,
                    'is_active' => true,
                ]);
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped_blank' => $skippedBlank, 'names' => $names];
    }

    /** @return array<int, string> */
    private static function extractFromSpreadsheet(string $filePath): array
    {
        if (! class_exists(IOFactory::class)) {
            throw new \RuntimeException(
                'Excel import requires the phpoffice/phpspreadsheet package. '
                .'Run: composer require phpoffice/phpspreadsheet — or upload a .csv or .docx file instead.'
            );
        }

        // CSV-specific: normalize line endings before parsing. PhpSpreadsheet's
        // CSV reader (like PHP's native fgetcsv/SplFileObject) does not
        // reliably handle old-Mac-style files that use a lone \r as the line
        // separator instead of \n or \r\n — confirmed directly against a
        // real failing upload, where the entire file was read as a single
        // unparseable line until \r was recognized as the actual separator.
        // PHP 8.1+ also removed the auto_detect_line_endings ini setting
        // that used to paper over this, so it has to be handled explicitly.
        // XLSX is a binary/zip format and is never touched by this — running
        // text normalization on it would corrupt the file.
        $readPath = $filePath;
        if (in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
            $raw = file_get_contents($filePath);
            if ($raw !== false) {
                $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
                if ($normalized !== $raw) {
                    $readPath = $filePath.'.normalized.csv';
                    file_put_contents($readPath, $normalized);
                }
            }
        }

        $spreadsheet = IOFactory::load($readPath);
        if ($readPath !== $filePath) {
            @unlink($readPath); // temp normalized copy, not the original upload
        }
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return [];
        }

        $headerRow = $rows[0] ?? [];

        // Find every column that's part of a person's name (Name, Full Name,
        // First Name, Last Name, Surname, Family Name) and combine them per
        // row — handles both a single "Name" column and a name split across
        // First/Last columns.
        $nameColumns = [];
        foreach ($headerRow as $index => $cellValue) {
            $header = strtolower(trim((string) $cellValue));
            if (str_contains($header, 'name') || str_contains($header, 'surname')) {
                $nameColumns[] = $index;
            }
        }

        $names = [];

        if (! empty($nameColumns)) {
            for ($i = 1; $i < count($rows); $i++) {
                $parts = [];
                foreach ($nameColumns as $columnIndex) {
                    $value = trim((string) ($rows[$i][$columnIndex] ?? ''));
                    if ($value !== '') {
                        $parts[] = $value;
                    }
                }
                if (! empty($parts)) {
                    $names[] = implode(' ', $parts);
                }
            }

            return $names;
        }

        // Fallback: no recognizable name column in the header — treat the
        // first non-empty, non-numeric cell per row as the name. Still
        // skips row 0 if IT looks like a header (any cell contains "name"),
        // so an unrecognized-but-still-a-header first row doesn't get
        // imported as if it were an intern.
        $firstRowLooksLikeHeader = collect($headerRow)->contains(
            fn ($c) => str_contains(strtolower(trim((string) $c)), 'name')
        );
        $startIndex = $firstRowLooksLikeHeader ? 1 : 0;

        for ($i = $startIndex; $i < count($rows); $i++) {
            foreach ($rows[$i] as $cell) {
                $cell = trim((string) $cell);
                if ($cell !== '' && ! is_numeric($cell)) {
                    $names[] = $cell;
                    break;
                }
            }
        }

        return $names;
    }

    /** @return array<int, string> */
    private static function extractFromWord(string $filePath): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Could not open the .docx file — it may be corrupted.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('This .docx file does not contain the expected document content.');
        }

        $dom = new \DOMDocument;
        $dom->loadXML($xml, LIBXML_NOWARNING | LIBXML_NOERROR);

        $tables = $dom->getElementsByTagName('w:tbl');
        if ($tables->length > 0) {
            return self::extractFromWordTable($tables->item(0));
        }

        // No table — fall back to one name per non-empty paragraph, for a
        // genuinely plain list document.
        $paragraphs = $dom->getElementsByTagName('w:p');
        $names = [];
        foreach ($paragraphs as $p) {
            $text = trim($p->textContent);
            if ($text !== '') {
                $names[] = $text;
            }
        }

        return $names;
    }

    /**
     * Extracts names from a Word table by locating the "Name" column via
     * its header text, then reading only that column from every data row —
     * NOT every paragraph in the table, which would also pick up serial
     * numbers, registration numbers, and other columns as if they were
     * names. Header row is assumed to be the first row; the name column is
     * whichever header cell contains "name" case-insensitively, same
     * heuristic used for spreadsheets.
     *
     * @return array<int, string>
     */
    private static function extractFromWordTable(\DOMElement $table): array
    {
        $rows = $table->getElementsByTagName('w:tr');
        if ($rows->length === 0) {
            return [];
        }

        $getCellTexts = function (\DOMElement $row): array {
            $cells = $row->getElementsByTagName('w:tc');
            $texts = [];
            foreach ($cells as $cell) {
                $texts[] = trim($cell->textContent);
            }

            return $texts;
        };

        $headerCells = $getCellTexts($rows->item(0));
        $nameColumnIndex = null;
        foreach ($headerCells as $i => $cellText) {
            if (str_contains(strtolower($cellText), 'name')) {
                $nameColumnIndex = $i;
                break;
            }
        }

        if ($nameColumnIndex === null) {
            throw new \RuntimeException(
                'Could not find a "Name" column in the table — found columns: '
                .implode(', ', $headerCells).'. Rename the name column to include "Name", or use a plain list instead of a table.'
            );
        }

        $names = [];
        for ($r = 1; $r < $rows->length; $r++) {
            $cells = $getCellTexts($rows->item($r));
            $name = trim($cells[$nameColumnIndex] ?? '');
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }
}
