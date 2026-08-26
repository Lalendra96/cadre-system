<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * PdfExportService
 *
 * Wraps barryvdh/laravel-dompdf with consistent A4 layout,
 * audit logging, and security headers for every PDF export
 * in the HIMS PARIKSHA Carder Management System.
 *
 * INSTALLATION
 * ────────────
 *   composer require barryvdh/laravel-dompdf
 *   php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
 *
 * USAGE
 * ─────
 *   return PdfExportService::make('pdf.snapshot', $data, 'snapshot-jan-2026.pdf')
 *       ->download();
 *
 *   // Stream in browser instead of forcing download:
 *   return PdfExportService::make('pdf.snapshot', $data, 'snapshot.pdf')->stream();
 *
 * ENVIRONMENT NOTES
 * ─────────────────
 * DomPDF is pure PHP — no wkhtmltopdf binary, no Node process.
 * Safe for air-gapped hospital LAN deployment.
 * Charts from Chart.js cannot be rendered; all PDFs use tabular layouts.
 */
class PdfExportService
{
    private \Barryvdh\DomPDF\PDF $pdf;
    private string $filename;
    private ?int $userId;
    private string $exportType;

    private function __construct(
        \Barryvdh\DomPDF\PDF $pdf,
        string $filename,
        string $exportType,
        ?int $userId
    ) {
        $this->pdf        = $pdf;
        $this->filename   = $filename;
        $this->exportType = $exportType;
        $this->userId     = $userId;
    }

    /**
     * Create a PDF from a Blade view.
     *
     * @param  string       $view        Blade view path (e.g. 'pdf.snapshot')
     * @param  array        $data        Data passed to the view
     * @param  string       $filename    Download filename (include .pdf)
     * @param  string       $orientation 'portrait' or 'landscape'
     * @param  string|null  $paper       Paper size: 'a4', 'a3', 'letter' etc.
     * @param  int|null     $userId      User ID for audit log (null = skip audit)
     * @param  string       $exportType  Audit log type key
     */
    public static function make(
        string  $view,
        array   $data,
        string  $filename,
        string  $orientation = 'portrait',
        string  $paper       = 'a4',
        ?int    $userId      = null,
        string  $exportType  = 'pdf_export',
    ): self {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)   // Security: no remote assets
            ->setOption('defaultFont', 'Arial')
            ->setOption('dpi', 150)
            ->setOption('debugKeepTemp', false);

        return new self($pdf, $filename, $exportType, $userId);
    }

    /**
     * Force-download the PDF and log the export.
     */
    public function download(?string $ipAddress = null, ?string $userAgent = null): Response
    {
        $this->logExport($ipAddress, $userAgent);

        return $this->pdf->download($this->filename);
    }

    /**
     * Stream the PDF inline (opens in browser PDF viewer).
     */
    public function stream(?string $ipAddress = null, ?string $userAgent = null): Response
    {
        $this->logExport($ipAddress, $userAgent);

        return $this->pdf->stream($this->filename);
    }

    private function logExport(?string $ip, ?string $ua): void
    {
        if ($this->userId === null) {
            return;
        }

        AuditLogService::logExport(
            $this->userId,
            $this->exportType,
            $this->filename,
            'pdf_report',
            null,
            null,
            $ip,
            $ua
        );
    }
}
