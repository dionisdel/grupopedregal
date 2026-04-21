<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cuenta ha sido aprobada — Grupo Pedregal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-approval',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
            ],
        );
    }
}
