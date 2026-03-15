<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes,
        public readonly ?string $accountLabel = null,
        public readonly string $viewName = 'emails.password-reset-code',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
            with: [
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
                'accountLabel' => $this->accountLabel,
            ],
        );
    }
}
