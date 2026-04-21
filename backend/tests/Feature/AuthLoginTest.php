<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_successfully(): void
    {
        User::factory()->create([
            'email' => 'active@test.com',
            'password' => Hash::make('secret123'),
            'estado' => 'activo',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'active@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_pending_user_gets_403_with_pending_message(): void
    {
        User::factory()->create([
            'email' => 'pending@test.com',
            'password' => Hash::make('secret123'),
            'estado' => 'pendiente',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pending@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Tu cuenta está pendiente de aprobación']);
    }

    public function test_inactive_user_gets_403_with_deactivated_message(): void
    {
        User::factory()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make('secret123'),
            'estado' => 'inactivo',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Tu cuenta ha sido desactivada']);
    }

    public function test_invalid_credentials_return_generic_error(): void
    {
        User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('secret123'),
            'estado' => 'activo',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['email' => ['Credenciales incorrectas']]);
    }

    public function test_nonexistent_email_returns_same_generic_error(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@test.com',
            'password' => 'anypassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['email' => ['Credenciales incorrectas']]);
    }

    public function test_pending_user_with_wrong_password_gets_generic_error_not_pending_message(): void
    {
        User::factory()->create([
            'email' => 'pending@test.com',
            'password' => Hash::make('secret123'),
            'estado' => 'pendiente',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pending@test.com',
            'password' => 'wrongpassword',
        ]);

        // Should NOT reveal that the account is pending — just generic error
        $response->assertStatus(422)
            ->assertJsonFragment(['email' => ['Credenciales incorrectas']]);
    }
}
