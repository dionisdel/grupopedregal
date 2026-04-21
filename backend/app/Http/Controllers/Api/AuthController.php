<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas'],
            ]);
        }

        // Verificar estado del usuario
        if ($user->estado === 'pendiente') {
            return response()->json([
                'message' => 'Tu cuenta está pendiente de aprobación',
            ], 403);
        }

        if ($user->estado === 'inactivo') {
            return response()->json([
                'message' => 'Tu cuenta ha sido desactivada',
            ], 403);
        }

        // Eliminar tokens anteriores
        $user->tokens()->delete();

        // Crear nuevo token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'slug' => $user->role->slug,
                ] : null,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
                'permissions' => $user->role->permissions->pluck('slug'),
            ] : null,
        ]);
    }

    public function register(Request $request, EmailService $emailService): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'nullable|string|max:50',
            'empresa' => 'required|string|max:255',
            'nif_cif' => 'required|string|max:50|unique:users,nif_cif',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'empresa' => $validated['empresa'],
            'nif_cif' => $validated['nif_cif'],
            'password' => $validated['password'],
            'estado' => 'pendiente',
        ]);

        $emailService->sendRegistrationNotification($user);

        return response()->json([
            'message' => 'Registro exitoso. Tu cuenta está pendiente de aprobación por un administrador.',
        ], 201);
    }
}
