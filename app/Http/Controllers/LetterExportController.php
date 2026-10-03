<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Services\AuditLogService;
use App\Services\LiveLetterDocxExporter;
use App\Services\PdfExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class LetterExportController extends Controller
{
    public function pdf(Request $request, Letter $letter): Response
    {
        $this->authorizeFinalExport($request, $letter);
        $letter->load(['letterhead', 'approvedBy', 'eSignature']);

        $signatureDataUri = null;
        if ($letter->eSignature && $letter->eSignature->fileExists()) {
            $signatureDataUri = 'data:'.$letter->eSignature->mime_type.';base64,'
                .base64_encode(Storage::disk('local')->get($letter->eSignature->file_path));
        }

        $filename = $this->filename($letter, 'pdf');

        AuditLogService::logExport(
            $request->user()->id,
            'live_letter_final_pdf',
            $filename,
            'letter',
            $letter->id,
            null,
            $request->ip(),
            $request->userAgent()
        );

        return PdfExportService::make(
            'letters.export.final',
            compact('letter', 'signatureDataUri'),
            $filename,
            'portrait',
            'a4',
            null,
            'live_letter_final_pdf'
        )->download($request->ip(), $request->userAgent());
    }

    public function docx(Request $request, Letter $letter, LiveLetterDocxExporter $exporter): BinaryFileResponse
    {
        $this->authorizeFinalExport($request, $letter);
        $letter->load(['letterhead', 'approvedBy', 'eSignature']);

        $path = $exporter->build($letter);
        $filename = $this->filename($letter, 'docx');

        AuditLogService::logExport(
            $request->user()->id,
            'live_letter_final_docx',
            $filename,
            'letter',
            $letter->id,
            filesize($path) ?: null,
            $request->ip(),
            $request->userAgent()
        );

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    private function authorizeFinalExport(Request $request, Letter $letter): void
    {
        abort_unless($letter->live_edit_enabled, 404);
        abort_unless(
            in_array($letter->workflow_status, [
                Letter::STATUS_APPROVED,
                Letter::STATUS_ISSUED,
                Letter::STATUS_ARCHIVED,
            ], true),
            422,
            'Only an approved, issued or archived official letter can be exported as a final copy.'
        );

        $user = $request->user();
        $recipient = $letter->recipients()->where('user_id', $user->id)->exists();

        abort_unless(
            $user->id === $letter->created_by || $recipient || $user->isSuperAdmin(),
            403,
            'You are not authorised to export this official letter.'
        );

        abort_if(
            $letter->approved_content_hash && ! hash_equals($letter->approved_content_hash, $letter->contentHash()),
            409,
            'Integrity verification failed. Export has been blocked because the current content no longer matches the approved hash.'
        );
    }

    private function filename(Letter $letter, string $extension): string
    {
        $base = $letter->reference_no ?: ('letter-'.$letter->id);
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?: ('letter-'.$letter->id);

        return $base.'-final.'.$extension;
    }
}
