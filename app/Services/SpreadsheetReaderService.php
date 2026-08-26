<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Reads header + data rows from an uploaded CSV or XLSX file.
 *
 * CSV — handled with PHP's built-in SplFileObject/fgetcsv, zero
 * dependencies, always available.
 *
 * XLSX — requires phpoffice/phpspreadsheet:
 *   composer require phpoffice/phpspreadsheet
 * Chosen over maatwebsite/excel because this use case only needs raw
 * cell reads (no Eloquent-import sugar, no queued jobs) — PhpSpreadsheet
 * directly is the lighter, more explicit dependency for that.
 *
 * DATA SECURITY: this class only ever reads from the path it's given —
 * callers are responsible for validating the upload (mime/size) and
 * storing it on the private disk before calling here. See
 * EmployeeImportController::store() for that validation.
 */
class SpreadsheetReaderService
{
    /**
     * @return array{headers: array<int,string>, rows: array<int,array<int,mixed>>}
     */
    public static function read(string $absolutePath, string $extension): array
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'csv', 'txt' => self::readCsv($absolutePath),
            'xlsx', 'xls' => self::readExcel($absolutePath),
            default => throw new \InvalidArgumentException("Unsupported file type: .{$extension}"),
        };
    }

    private static function readCsv(string $path): array
    {
        $file = new \SplFileObject($path, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

        $headers = null;
        $rows    = [];

        foreach ($file as $line) {
            if ($line === [null] || $line === false) {
                continue;
            }
            // Strip a UTF-8 BOM from the very first header cell if present —
            // common with CSVs exported from Excel on Windows.
            if ($headers === null) {
                if (isset($line[0])) {
                    $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $line[0]);
                }
                $headers = array_map(fn ($h) => trim((string) $h), $line);
                continue;
            }
            $rows[] = $line;
        }

        return ['headers' => $headers ?? [], 'rows' => $rows];
    }

    private static function readExcel(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException(
                'Excel (.xlsx) import requires the phpoffice/phpspreadsheet package. '
                . 'Run: composer require phpoffice/phpspreadsheet — or upload a .csv file instead.'
            );
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false); // numeric-indexed rows/cols

        if (empty($data)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map(fn ($h) => trim((string) ($h ?? '')), array_shift($data));

        // Drop fully-empty trailing rows (common when a sheet has formatting
        // applied past the last real data row).
        $rows = array_values(array_filter($data, function ($row) {
            return ! empty(array_filter($row, fn ($cell) => trim((string) ($cell ?? '')) !== ''));
        }));

        return ['headers' => $headers, 'rows' => $rows];
    }
}
