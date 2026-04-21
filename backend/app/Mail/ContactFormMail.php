<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('info@grupopedregal.es', 'Grupo Pedregal'),
            replyTo: [new Address($this->data['email'], $this->data['nombre'])],
            subject: 'Nuevo mensaje de contacto: ' . $this->data['asunto'],
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
        $nombre = e($this->data['nombre']);
        $email = e($this->data['email']);
        $telefono = e($this->data['telefono'] ?? 'No proporcionado');
        $empresa = e($this->data['empresa'] ?? 'No proporcionada');
        $lineaNegocio = e($this->data['linea_negocio']);
        $asunto = e($this->data['asunto']);
        $mensaje = nl2br(e($this->data['mensaje']));

        return <<<HTML
        <h2>Nuevo mensaje de contacto</h2>
        <p>Se ha recibido un nuevo mensaje desde el formulario de contacto del portal:</p>
        <table style="border-collapse: collapse; width: 100%; max-width: 600px;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Nombre</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{$nombre}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Email</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{$email}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Teléfono</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{$telefono}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Empresa</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{$empresa}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Línea de negocio</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{$lineaNegocio}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Asunto</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{$asunto}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; vertical-align: top;">Mensaje</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{$mensaje}</td>
            </tr>
        </table>
        HTML;
    }
}
