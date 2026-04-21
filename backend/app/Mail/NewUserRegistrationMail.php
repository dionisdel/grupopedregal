<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('info@grupopedregal.es', 'Grupo Pedregal'),
            subject: 'Nuevo registro de usuario pendiente de aprobación',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $name = e($this->user->name);
        $email = e($this->user->email);
        $empresa = e($this->user->empresa);
        $nifCif = e($this->user->nif_cif);
        $telefono = e($this->user->telefono ?? 'No proporcionado');

        return <<<HTML
        <h2>Nuevo registro de usuario</h2>
        <p>Se ha registrado un nuevo usuario que requiere aprobación:</p>
        <ul>
            <li><strong>Nombre:</strong> {$name}</li>
            <li><strong>Email:</strong> {$email}</li>
            <li><strong>Empresa:</strong> {$empresa}</li>
            <li><strong>NIF/CIF:</strong> {$nifCif}</li>
            <li><strong>Teléfono:</strong> {$telefono}</li>
        </ul>
        <p>Acceda al panel de administración para aprobar o rechazar este registro.</p>
        HTML;
    }
}
