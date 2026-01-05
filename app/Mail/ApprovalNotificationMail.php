<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Permohonan Keanggotaan Koperasi Syariah Warga Polban',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approval-notification',
            with: [
                'user' => $this->user,
            ],
        );
    }
}
