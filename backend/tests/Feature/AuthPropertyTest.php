<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature: web-portal-product-catalog
 *
 * Property-based tests for authentication, registration, and permission enforcement.
 * Uses Faker with iteration loops to simulate PBT (100+ iterations).
 */
class AuthPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const PBT_ITERATIONS = 100;

    /**
     * Feature: web-portal-product-catalog, Property 12: Generic authentication error message
     *
     * For any invalid credential combination (non-existent email, wrong password, or both),
     * the login response should return the same generic error message without revealing
     * whether the email exists in the system.
     *
     * **Validates: Requirements 6.3**
     */
    public function test_property12_generic_authentication_error_message(): void
    {
        // Create a pool of existing users to test against
        $existingUsers = [];
        for ($i = 0; $i < 5; $i++) {
            $existingUsers[] = User::factory()->create([
                'password' => Hash::make('correct-password-' . $i),
                'estado' => 'activo',
            ]);
        }

        $expectedErrorMessage = 'Credenciales incorrectas';

        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Randomly pick one of three invalid credential scenarios
            $scenario = fake()->numberBetween(1, 3);

            switch ($scenario) {
                case 1:
                    // Non-existent email with random password
                    $email = fake()->unique()->safeEmail();
                    $password = fake()->password(8, 20);
                    break;
                case 2:
                    // Existing email with wrong password
                    $user = fake()->randomElement($existingUsers);
                    $email = $user->email;
                    $password = 'wrong-' . fake()->password(8, 20);
                    break;
                case 3:
                    // Non-existent email with a password that belongs to some user
                    $email = fake()->unique()->safeEmail();
                    $userIndex = fake()->numberBetween(0, count($existingUsers) - 1);
                    $password = 'correct-password-' . $userIndex;
                    break;
            }

            $response = $this->postJson('/api/login', [
                'email' => $email,
                'password' => $password,
            ]);

            // All invalid credential scenarios must return 422 with the same generic message
            $response->assertStatus(422,
                "Iteration {$i} (scenario {$scenario}): Expected 422 for email={$email}"
            );

            $responseData = $response->json();
            $this->assertArrayHasKey('errors', $responseData,
                "Iteration {$i} (scenario {$scenario}): Response must contain 'errors' key"
            );
            $this->assertArrayHasKey('email', $responseData['errors'],
                "Iteration {$i} (scenario {$scenario}): Errors must contain 'email' key"
            );
            $this->assertContains($expectedErrorMessage, $responseData['errors']['email'],
                "Iteration {$i} (scenario {$scenario}): Error message must be the generic '{$expectedErrorMessage}'"
            );
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 13: Registration creates user with pending status
     *
     * For any valid registration data (unique email, unique NIF/CIF, non-empty required fields),
     * the created user should have estado='pendiente'.
     *
     * **Validates: Requirements 6.5**
     */
    public function test_property13_registration_creates_user_with_pending_status(): void
    {
        // Disable throttle middleware to allow 100+ registration attempts in test
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            $email = fake()->unique()->safeEmail();
            $nifCif = fake()->unique()->numerify('########') . fake()->randomLetter();
            $nombre = fake()->name();
            $empresa = fake()->company();
            $telefono = fake()->phoneNumber();
            $password = fake()->password(8, 20);

            $response = $this->postJson('/api/register', [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'empresa' => $empresa,
                'nif_cif' => $nifCif,
                'password' => $password,
            ]);

            $response->assertStatus(201,
                "Iteration {$i}: Registration should succeed for email={$email}, nif_cif={$nifCif}"
            );

            // Verify the user was created with estado='pendiente'
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user,
                "Iteration {$i}: User with email={$email} should exist in database"
            );
            $this->assertEquals('pendiente', $user->estado,
                "Iteration {$i}: User estado should be 'pendiente', got '{$user->estado}'"
            );
            $this->assertEquals($nombre, $user->name,
                "Iteration {$i}: User name should match registration data"
            );
            $this->assertEquals($nifCif, $user->nif_cif,
                "Iteration {$i}: User nif_cif should match registration data"
            );
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 14: Registration rejects duplicate identifiers
     *
     * For any registration attempt where the email or NIF/CIF already exists in the system,
     * the API should reject the request with a validation error and not create a new user.
     *
     * **Validates: Requirements 6.8**
     */
    public function test_property14_registration_rejects_duplicate_identifiers(): void
    {
        // Disable throttle middleware to allow 100+ registration attempts in test
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Create an existing user first
            $existingUser = User::factory()->create([
                'nif_cif' => fake()->unique()->numerify('########') . fake()->randomLetter(),
                'empresa' => fake()->company(),
                'estado' => fake()->randomElement(['activo', 'pendiente', 'inactivo']),
            ]);

            $scenario = fake()->numberBetween(1, 3);
            $registrationData = [
                'nombre' => fake()->name(),
                'telefono' => fake()->phoneNumber(),
                'empresa' => fake()->company(),
                'password' => fake()->password(8, 20),
            ];

            switch ($scenario) {
                case 1:
                    // Duplicate email, unique NIF/CIF
                    $registrationData['email'] = $existingUser->email;
                    $registrationData['nif_cif'] = fake()->unique()->numerify('########') . fake()->randomLetter();
                    $expectedErrorField = 'email';
                    break;
                case 2:
                    // Unique email, duplicate NIF/CIF
                    $registrationData['email'] = fake()->unique()->safeEmail();
                    $registrationData['nif_cif'] = $existingUser->nif_cif;
                    $expectedErrorField = 'nif_cif';
                    break;
                case 3:
                    // Both duplicate
                    $registrationData['email'] = $existingUser->email;
                    $registrationData['nif_cif'] = $existingUser->nif_cif;
                    // Either field could be reported
                    $expectedErrorField = null;
                    break;
            }

            $userCountBefore = User::count();

            $response = $this->postJson('/api/register', $registrationData);

            $response->assertStatus(422,
                "Iteration {$i} (scenario {$scenario}): Should reject duplicate registration"
            );

            $responseData = $response->json();
            $this->assertArrayHasKey('errors', $responseData,
                "Iteration {$i} (scenario {$scenario}): Response must contain 'errors' key"
            );

            if ($expectedErrorField) {
                $this->assertArrayHasKey($expectedErrorField, $responseData['errors'],
                    "Iteration {$i} (scenario {$scenario}): Errors must contain '{$expectedErrorField}' key"
                );
            } else {
                // For scenario 3 (both duplicate), at least one of the fields should have an error
                $hasEmailError = isset($responseData['errors']['email']);
                $hasNifError = isset($responseData['errors']['nif_cif']);
                $this->assertTrue($hasEmailError || $hasNifError,
                    "Iteration {$i} (scenario {$scenario}): At least one duplicate field should have an error"
                );
            }

            // No new user should have been created
            $this->assertEquals($userCountBefore, User::count(),
                "Iteration {$i} (scenario {$scenario}): No new user should be created for duplicate registration"
            );
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 18: Permission enforcement returns 403
     *
     * For any authenticated user whose role lacks a specific permission, attempting to access
     * an endpoint protected by that permission should return HTTP 403 Forbidden.
     *
     * **Validates: Requirements 9.4, 9.7**
     */
    public function test_property18_permission_enforcement_returns_403(): void
    {
        // Register a test route that uses the permission middleware
        Route::middleware(['auth:sanctum', 'permission:test.permission'])->get(
            '/api/test-permission-endpoint',
            fn () => response()->json(['message' => 'Access granted'])
        );

        // Define a pool of permission slugs to test with
        $permissionSlugs = [
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar',
            'clientes.ver', 'clientes.crear', 'usuarios.ver', 'usuarios.crear',
            'precios.ver', 'portal.ver', 'portal.editar',
        ];

        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Create a permission that the test route requires
            $requiredPermission = Permission::firstOrCreate(
                ['slug' => 'test.permission'],
                ['name' => 'Test Permission', 'description' => 'Test', 'module' => 'test']
            );

            // Create a role with a random subset of permissions that does NOT include the required one
            $role = Role::create([
                'name' => 'Test Role ' . $i,
                'slug' => 'test-role-' . $i . '-' . fake()->unique()->randomNumber(6),
                'description' => 'Test role for iteration ' . $i,
                'activo' => true,
            ]);

            // Assign random permissions from the pool (none of which is 'test.permission')
            $numPermissions = fake()->numberBetween(0, min(5, count($permissionSlugs)));
            $selectedSlugs = fake()->randomElements($permissionSlugs, $numPermissions);

            foreach ($selectedSlugs as $slug) {
                $perm = Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => ucfirst(str_replace('.', ' ', $slug)), 'description' => $slug, 'module' => explode('.', $slug)[0]]
                );
                $role->permissions()->syncWithoutDetaching([$perm->id]);
            }

            // Create a user with this role
            $user = User::factory()->create([
                'role_id' => $role->id,
                'estado' => 'activo',
            ]);

            // Attempt to access the protected endpoint
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/test-permission-endpoint');

            $response->assertStatus(403,
                "Iteration {$i}: User with role '{$role->slug}' (permissions: [" . implode(', ', $selectedSlugs) . "]) should get 403 for 'test.permission'"
            );

            $responseData = $response->json();
            $this->assertArrayHasKey('message', $responseData,
                "Iteration {$i}: 403 response must contain 'message' key"
            );
            $this->assertEquals('No tienes permisos para esta acción.', $responseData['message'],
                "Iteration {$i}: 403 message should be 'No tienes permisos para esta acción.'"
            );
        }
    }

    /**
     * Complementary test: verify that a user WITH the required permission gets 200.
     * This ensures the middleware works correctly in both directions.
     */
    public function test_property18_permission_enforcement_allows_authorized_user(): void
    {
        Route::middleware(['auth:sanctum', 'permission:test.allowed'])->get(
            '/api/test-allowed-endpoint',
            fn () => response()->json(['message' => 'Access granted'])
        );

        for ($i = 0; $i < 20; $i++) {
            $permission = Permission::firstOrCreate(
                ['slug' => 'test.allowed'],
                ['name' => 'Test Allowed', 'description' => 'Test', 'module' => 'test']
            );

            $role = Role::create([
                'name' => 'Allowed Role ' . $i,
                'slug' => 'allowed-role-' . $i . '-' . fake()->unique()->randomNumber(6),
                'description' => 'Allowed role',
                'activo' => true,
            ]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);

            $user = User::factory()->create([
                'role_id' => $role->id,
                'estado' => 'activo',
            ]);

            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/test-allowed-endpoint');

            $response->assertStatus(200,
                "Iteration {$i}: User with 'test.allowed' permission should get 200"
            );
        }
    }
}
