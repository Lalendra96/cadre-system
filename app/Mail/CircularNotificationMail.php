<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Circular;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The email sent to each employee when a Circular is distributed to a
 * Position Group. This is the FIRST email-sending code in this system —
 * everything else uses in-app notifications only.
 *
 * REQUIRED SETUP (not needed anywhere else in this system):
 *   Add real mail credentials to .env — without this, sending will fail
 *   or silently no-op depending on your MAIL_MAILER setting:
 *
 *     MAIL_MAILER=smtp
 *     MAIL_HOST=smtp.your-provider.com
 *     MAIL_PORT=587
 *     MAIL_USERNAME=...
 *     MAIL_PASSWORD=...
 *     MAIL_ENCRYPTION=tls
 *     MAIL_FROM_ADDRESS=notices@your-hospital-domain.lk
 *     MAIL_FROM_NAME="HIMS PARIKSHA"
 *
 * Not queued — CircularController::sendToGroups() sends synchronously in
 * a loop with per-recipient try/catch, matching this codebase's existing
 * synchronous-batch pattern (see EmployeeImportController). For a very
 * large group, consider adding a queue connection and implementing
 * ShouldQueue on this class later — that is a small, additive change,
 * not a rewrite, since Mailable already supports it natively.
 */
class CircularNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Circular $circular, public string $recipientName)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . (Circular::CATEGORY_LABELS[$this->circular->category] ?? 'Notice') . '] ' . $this->circular->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.circular-notification',
            with: [
                'circular'      => $this->circular,
                'recipientName' => $this->recipientName,
                'publicUrl'     => route('circulars.public-show', $this->circular->share_token),
            ],
        );
    }
}
