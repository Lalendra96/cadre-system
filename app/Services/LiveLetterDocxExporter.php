<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Letter;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use ZipStream\ZipStream;

class LiveLetterDocxExporter
{
    public function build(Letter $letter): string
    {
        $letter->loadMissing(['letterhead', 'approvedBy', 'eSignature']);

        $tmp = tempnam(sys_get_temp_dir(), 'live-letter-');
        if ($tmp === false) {
            throw new \RuntimeException('Unable to create temporary DOCX file.');
        }

        $docxPath = $tmp.'.docx';
        @unlink($tmp);

        [$signaturePart, $signatureRelationship, $signatureDrawing] = $this->signatureParts($letter);

        $parts = [
            '[Content_Types].xml' => $this->contentTypes($signaturePart['extension'] ?? null),
            '_rels/.rels' => $this->packageRelationships(),
            'word/_rels/document.xml.rels' => $this->documentRelationships($signatureRelationship),
            'word/document.xml' => $this->documentXml($letter, $signatureDrawing),
            'word/styles.xml' => $this->stylesXml(),
        ];

        if ($signaturePart !== null) {
            $parts['word/media/signature.'.$signaturePart['extension']] = $signaturePart['bytes'];
        }

        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive;
            if ($zip->open($docxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create DOCX archive.');
            }

            foreach ($parts as $name => $contents) {
                $zip->addFromString($name, $contents);
            }
            $zip->close();

            return $docxPath;
        }

        if (class_exists(ZipStream::class)) {
            $stream = fopen($docxPath, 'w+b');
            if ($stream === false) {
                throw new \RuntimeException('Unable to open temporary DOCX stream.');
            }

            $zip = new ZipStream(
                outputName: basename($docxPath),
                sendHttpHeaders: false,
                outputStream: $stream,
            );

            foreach ($parts as $name => $contents) {
                $zip->addFile(fileName: $name, data: $contents);
            }
            $zip->finish();
            fclose($stream);

            return $docxPath;
        }

        throw new \RuntimeException('DOCX export requires PHP ZipArchive or the installed ZipStream package.');
    }

    private function documentXml(Letter $letter, string $signatureDrawing): string
    {
        $headData = $letter->officialLetterheadData();
        $head = $headData !== [] ? (object) $headData : null;
        $body = [];

        foreach (preg_split('/\R/u', (string) $letter->live_content) ?: [] as $line) {
            $body[] = $this->paragraph($line, false, 'left', 220);
        }

        $institution = $head?->institution_name ?: 'Carder Management';
        $ministry = $head?->ministry_name ?: 'Ministry of Health - Sri Lanka';
        $department = $head?->department_name;
        $address = trim(implode(', ', array_filter([$head?->address_line_1, $head?->address_line_2])));
        $contact = trim(implode(' | ', array_filter([
            $head?->telephone ? 'Tel: '.$head->telephone : null,
            $head?->email,
            $head?->website,
        ])));
        $approvalDate = optional($letter->approved_at)->format('d M Y H:i') ?: '—';
        $approvedBy = $letter->approvedBy?->name ?: 'Authorised approver';
        $designation = $head?->signatory_designation ?: 'Authorised Officer';
        $hash = $letter->approved_content_hash ?: $letter->contentHash();

        $sections = [
            $this->paragraph($institution, true, 'center', 320),
            $this->paragraph($ministry, false, 'center', 220),
        ];

        if ($department) {
            $sections[] = $this->paragraph($department, false, 'center', 200);
        }
        if ($address) {
            $sections[] = $this->paragraph($address, false, 'center', 180);
        }
        if ($contact) {
            $sections[] = $this->paragraph($contact, false, 'center', 180);
        }
        if ($head?->header_note) {
            $sections[] = $this->paragraph($head->header_note, false, 'center', 180);
        }

        $sections[] = $this->paragraph('Reference: '.($letter->reference_no ?: '—'), false, 'left', 200);
        $sections[] = $this->paragraph('Date: '.optional($letter->issued_at ?? $letter->approved_at)->format('d M Y'), false, 'right', 200);
        $sections[] = $this->paragraph($letter->title, true, 'center', 280);
        $sections = array_merge($sections, $body);
        $sections[] = $this->paragraph('', false, 'left', 180);

        if ($signatureDrawing !== '') {
            $sections[] = '<w:p><w:pPr><w:jc w:val="left"/></w:pPr><w:r>'.$signatureDrawing.'</w:r></w:p>';
        }

        $sections[] = $this->paragraph($approvedBy, true, 'left', 200);
        $sections[] = $this->paragraph($designation, false, 'left', 190);
        $sections[] = $this->paragraph('Electronically approved: '.$approvalDate, false, 'left', 170);
        $sections[] = $this->paragraph('Integrity SHA-256: '.$hash, false, 'left', 145);
        $sections[] = $this->paragraph('Classification: '.ucfirst((string) $letter->document_classification), true, 'center', 160);

        if ($head?->footer_note) {
            $sections[] = $this->paragraph($head->footer_note, false, 'center', 155);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            .'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            .'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<w:body>'.implode('', $sections)
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    private function paragraph(string $text, bool $bold, string $align, int $size): string
    {
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $boldXml = $bold ? '<w:b/>' : '';

        return '<w:p><w:pPr><w:jc w:val="'.$align.'"/></w:pPr><w:r><w:rPr>'
            .$boldXml.'<w:sz w:val="'.$size.'"/></w:rPr><w:t xml:space="preserve">'
            .$text.'</w:t></w:r></w:p>';
    }

    private function signatureParts(Letter $letter): array
    {
        $signature = $letter->eSignature;
        if (! $signature || ! $signature->fileExists()) {
            return [null, '', ''];
        }

        $bytes = Storage::disk('local')->get($signature->file_path);
        $extension = match ($signature->mime_type) {
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            default => 'png',
        };

        $relationship = '<Relationship Id="rIdSignature" '
            .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
            .'Target="media/signature.'.$extension.'"/>';

        $drawing = '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="1905000" cy="762000"/><wp:docPr id="1" name="Approved Signature"/>'
            .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="signature.'.$extension.'"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="rIdSignature"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1905000" cy="762000"/></a:xfrm>'
            .'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic>'
            .'</a:graphicData></a:graphic></wp:inline></w:drawing>';

        return [[
            'bytes' => $bytes,
            'extension' => $extension,
        ], $relationship, $drawing];
    }

    private function contentTypes(?string $signatureExtension): string
    {
        $imageType = '';
        if ($signatureExtension) {
            $mime = $signatureExtension === 'jpg' ? 'image/jpeg' : ($signatureExtension === 'gif' ? 'image/gif' : 'image/png');
            $imageType = '<Default Extension="'.$signatureExtension.'" ContentType="'.$mime.'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .$imageType
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>';
    }

    private function packageRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
    }

    private function documentRelationships(string $signatureRelationship): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .$signatureRelationship
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/>'
            .'<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/></w:rPr></w:style>'
            .'</w:styles>';
    }
}
