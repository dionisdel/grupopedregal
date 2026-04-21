<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request, EmailService $emailService): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'empresa' => 'nullable|string|max:255',
            'linea_negocio' => 'required|in:PEDREGAL,Saturno PORT,Rentapró,General',
            'asunto' => 'required|string|max:255',
            'mensaje' => 'required|string|max:5000',
        ]);

        $contactMessage = ContactMessage::create([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'empresa' => $validated['empresa'] ?? null,
            'linea_negocio' => $validated['linea_negocio'],
            'asunto' => $validated['asunto'],
            'mensaje' => $validated['mensaje'],
            'enviado' => false,
        ]);

        $sent = $emailService->sendContactForm($validated);

        if ($sent) {
            $contactMessage->update(['enviado' => true]);
        }

        return response()->json([
            'message' => 'Mensaje enviado correctamente. Nos pondremos en contacto contigo lo antes posible.',
        ], 201);
    }
}
