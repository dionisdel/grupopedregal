<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class QuotePdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $productName,
        private readonly string $pdfContent,
        private readonly string $productSlug,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address('info@grupopedregal.es', 'Grupo Pedregal'),
            subject: "Presupuesto - {$this->productName} | Grupo Pedregal",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $filename = 'presupuesto-' . Str::slug($this->productName) . '.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfContent, $filename)
                ->withMime('application/pdf'),
        ];
    }

    private function buildHtml(): string
    {
        $name = e($this->productName);

        return <<<HTML
        <p>Adjunto encontrará el presupuesto de {$name}</p>
        HTML;
    }
}
