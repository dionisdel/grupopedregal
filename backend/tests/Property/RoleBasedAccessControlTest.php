<?php

namespace Tests\Property;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 11: Role-Based API Access Control
 *
 * Property-based test that tests protected admin endpoints with users of
 * different roles, verifying 403 for unauthorized and success for authorized.
 *
 * **Validates: Requirements 1.8**
 */
class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 20;

    /**
     * Admin endpoints that require admin/superadmin role.
     * Each entry: [method, uri]
     */
    private const ADMIN_ENDPOINTS = [
        ['GET', '/api/admin/categories'],
        ['GET', '/api/admin/products'],
    ];

    /**
     * Create a user with the given role slug.
     */
    private function createUserWithRole(string $roleSlug): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst($roleSlug), 'description' => "Test $roleSlug role", 'activo' => true]
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'estado' => 'activo',
        ]);
    }

    /**
     * @test
     * Property 11: Role-Based API Access Control
     *
     * For any protected admin endpoint:
     * - superadmin SHALL receive a successful response (not 401/403)
     * - admin SHALL receive a successful response (not 401/403)
     * - cliente SHALL receive HTTP 403
     * - unauthenticated (publico) SHALL receive HTTP 401
     *
     * Validates: Requirements 1.8
     */
    public function admin_endpoints_enforce_role_restrictions(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Pick a random admin endpoint
            $endpointIdx = mt_rand(0, count(self::ADMIN_ENDPOINTS) - 1);
            [$method, $uri] = self::ADMIN_ENDPOINTS[$endpointIdx];

            // Pick a random role to test
            $roles = ['superadmin', 'admin', 'cliente', 'publico'];
            $roleSlug = $roles[mt_rand(0, count($roles) - 1)];

            if ($roleSlug === 'publico') {
                // Ensure no auth state from previous iteration
                $this->app['auth']->forgetGuards();

                // Unauthenticated request — should get 401 or 403 (depending on middleware order)
                $response = $this->json($method, $uri);

                $this->assertContains(
                    $response->getStatusCode(),
                    [401, 403],
                    "Iteration $i: Unauthenticated request to $method $uri should return 401 or 403, got {$response->getStatusCode()}"
                );
            } else {
                $user = $this->createUserWithRole($roleSlug);

                $response = $this->actingAs($user)->json($method, $uri);

                if ($roleSlug === 'cliente') {
                    // cliente should get 403
                    $this->assertEquals(
                        403,
                        $response->getStatusCode(),
                        "Iteration $i: User with role '$roleSlug' on $method $uri should get 403, got {$response->getStatusCode()}"
                    );
                } else {
                    // superadmin and admin should NOT get 401 or 403
                    $this->assertNotContains(
                        $response->getStatusCode(),
                        [401, 403],
                        "Iteration $i: User with role '$roleSlug' on $method $uri should succeed, got {$response->getStatusCode()}"
                    );
                }

                $user->forceDelete();
            }
        }
    }

    /**
     * @test
     * Validates: Requirements 1.8
     *
     * Exhaustive check: every role against every admin endpoint.
     */
    public function all_roles_checked_against_all_admin_endpoints(): void
    {
        $authorizedRoles = ['superadmin', 'admin'];
        $unauthorizedRoles = ['cliente'];

        foreach (self::ADMIN_ENDPOINTS as [$method, $uri]) {
            // Authorized roles should succeed
            foreach ($authorizedRoles as $roleSlug) {
                $user = $this->createUserWithRole($roleSlug);
                $response = $this->actingAs($user)->json($method, $uri);

                $this->assertNotContains(
                    $response->getStatusCode(),
                    [401, 403],
                    "Role '$roleSlug' on $method $uri should succeed, got {$response->getStatusCode()}"
                );

                $user->forceDelete();
            }

            // Unauthorized roles should get 403
            foreach ($unauthorizedRoles as $roleSlug) {
                $user = $this->createUserWithRole($roleSlug);
                $response = $this->actingAs($user)->json($method, $uri);

                $this->assertEquals(
                    403,
                    $response->getStatusCode(),
                    "Role '$roleSlug' on $method $uri should get 403, got {$response->getStatusCode()}"
                );

                $user->forceDelete();
            }

            // Unauthenticated should get 401 or 403 (depending on middleware order)
            $response = $this->json($method, $uri);
            $this->assertContains(
                $response->getStatusCode(),
                [401, 403],
                "Unauthenticated request to $method $uri should get 401 or 403, got {$response->getStatusCode()}"
            );
        }
    }
}
