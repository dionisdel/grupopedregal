<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_active_level_1_categories(): void
    {
        Category::factory()->create(['nivel' => 1, 'activo' => true, 'orden' => 1]);
        Category::factory()->create(['nivel' => 1, 'activo' => false, 'orden' => 2]);
        Category::factory()->create(['nivel' => 2, 'activo' => true, 'orden' => 3]);

        $response = $this->getJson('/api/categories/public');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_returns_correct_fields(): void
    {
        Category::factory()->create([
            'nivel' => 1,
            'activo' => true,
            'nombre' => 'Yesos',
            'slug' => 'yesos',
            'descripcion_web' => 'Yesos profesionales',
            'imagen_url' => '/images/yesos.jpg',
            'orden' => 1,
        ]);

        $response = $this->getJson('/api/categories/public');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'nombre' => 'Yesos',
                'slug' => 'yesos',
                'descripcion_web' => 'Yesos profesionales',
                'imagen_url' => '/images/yesos.jpg',
                'orden' => 1,
            ]);
    }

    public function test_categories_ordered_by_orden_ascending(): void
    {
        Category::factory()->create(['nivel' => 1, 'activo' => true, 'orden' => 3, 'nombre' => 'Tercero']);
        Category::factory()->create(['nivel' => 1, 'activo' => true, 'orden' => 1, 'nombre' => 'Primero']);
        Category::factory()->create(['nivel' => 1, 'activo' => true, 'orden' => 2, 'nombre' => 'Segundo']);

        $response = $this->getJson('/api/categories/public');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Primero', $data[0]['nombre']);
        $this->assertEquals('Segundo', $data[1]['nombre']);
        $this->assertEquals('Tercero', $data[2]['nombre']);
    }

    public function test_returns_empty_array_when_no_categories(): void
    {
        $response = $this->getJson('/api/categories/public');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_endpoint_accessible_without_authentication(): void
    {
        $response = $this->getJson('/api/categories/public');

        $response->assertStatus(200);
    }
}
