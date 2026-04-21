<?php

namespace App\Services;

use App\Mail\AccountApprovalMail;
use App\Mail\ContactFormMail;
use App\Mail\NewUserRegistrationMail;
use App\Mail\QuotePdfMail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Envía un email con el PDF del presupuesto adjunto.
     *
     * @param  string   $toEmail     Dirección de email destino
     * @param  string   $pdfContent  Contenido binario del PDF
     * @param  Product  $product     Producto asociado al presupuesto
     * @return bool
     */
    public function sendQuotePdf(string $toEmail, string $pdfContent, Product $product): bool
    {
        try {
            Mail::to($toEmail)->send(
                new QuotePdfMail(
                    productName: $product->nombre,
                    pdfContent: $pdfContent,
                    productSlug: $product->slug ?? '',
                )
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando email de presupuesto', [
                'to' => $toEmail,
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envía notificación al admin cuando un nuevo usuario se registra.
     */
    public function sendRegistrationNotification(User $user): bool
    {
        try {
            Mail::to('info@grupopedregal.es')->send(
                new NewUserRegistrationMail($user)
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando notificación de registro', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envía email de confirmación cuando se aprueba la cuenta de un usuario.
     */
    public function sendAccountApproval(User $user): bool
    {
        try {
            Mail::to($user->email)->send(
                new AccountApprovalMail($user)
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando email de aprobación de cuenta', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envía los datos del formulario de contacto a info@grupopedregal.es.
     */
    public function sendContactForm(array $data): bool
    {
        try {
            Mail::to('info@grupopedregal.es')->send(
                new ContactFormMail($data)
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando formulario de contacto', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
