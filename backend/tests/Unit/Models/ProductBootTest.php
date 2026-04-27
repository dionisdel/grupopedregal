<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Services\PriceCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBootTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function saving_event_auto_calculates_all_price_fields(): void
    {
        $product = Product::factory()->create([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 10,
            'coste_transporte' => 5,
            'iva_porcentaje' => 21,
            'desc_camion_vip' => 15,
            'desc_camion' => 12,
            'desc_oferta' => 20,
            'desc_vip' => 10,
            'desc_empresas' => 8,
            'desc_empresas_a' => 5,
            'metros_articulo' => null,
        ]);

        $expected = PriceCalculatorService::calculate([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 10,
            'coste_transporte' => 5,
            'iva_porcentaje' => 21,
            'desc_camion_vip' => 15,
            'desc_camion' => 12,
            'desc_oferta' => 20,
            'desc_vip' => 10,
            'desc_empresas' => 8,
            'desc_empresas_a' => 5,
            'metros_articulo' => null,
        ]);

        $product->refresh();

        foreach ($expected as $field => $value) {
            $this->assertEquals($value, $product->{$field}, "Field {$field} mismatch");
        }
    }

    /** @test */
    public function saving_event_handles_metros_articulo_for_m2_fields(): void
    {
        $product = Product::factory()->create([
            'pvp_proveedor' => 50,
            'desc_prov_1' => 5,
            'coste_transporte' => 3,
            'iva_porcentaje' => 21,
            'metros_articulo' => 1.5,
        ]);

        $product->refresh();

        $this->assertNotNull($product->coste_neto_m2);
        $this->assertNotNull($product->coste_m2_trans);
        $this->assertEquals($product->coste_neto, $product->coste_neto_m2);
        $this->assertEquals(
            round($product->coste_neto_m2 + 3, 4),
            $product->coste_m2_trans
        );
    }

    /** @test */
    public function saving_event_sets_null_m2_fields_when_no_metros(): void
    {
        $product = Product::factory()->create([
            'pvp_proveedor' => 50,
            'metros_articulo' => null,
        ]);

        $product->refresh();

        $this->assertNull($product->coste_neto_m2);
        $this->assertNull($product->coste_m2_trans);
    }

    /** @test */
    public function saved_event_creates_price_history_on_update(): void
    {
        $product = Product::factory()->create([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 10,
        ]);

        // Re-fetch from DB to clear wasRecentlyCreated flag
        $product = Product::find($product->id);

        // Update an editable price field
        $product->pvp_proveedor = 120;
        $product->save();

        $history = ProductPriceHistory::where('product_id', $product->id)
            ->where('field_changed', 'pvp_proveedor')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals(100, $history->old_value);
        $this->assertEquals(120, $history->new_value);
        $this->assertNotNull($history->changed_at);
    }

    /** @test */
    public function saved_event_does_not_create_history_on_first_create(): void
    {
        Product::factory()->create([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 10,
        ]);

        $this->assertDatabaseCount('product_price_history', 0);
    }

    /** @test */
    public function saved_event_tracks_multiple_field_changes(): void
    {
        $product = Product::factory()->create([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 10,
            'iva_porcentaje' => 21,
        ]);

        // Re-fetch from DB to clear wasRecentlyCreated flag
        $product = Product::find($product->id);

        $product->pvp_proveedor = 150;
        $product->desc_prov_1 = 15;
        $product->iva_porcentaje = 10;
        $product->save();

        $histories = ProductPriceHistory::where('product_id', $product->id)->get();

        $this->assertCount(3, $histories);

        $fields = $histories->pluck('field_changed')->toArray();
        $this->assertContains('pvp_proveedor', $fields);
        $this->assertContains('desc_prov_1', $fields);
        $this->assertContains('iva_porcentaje', $fields);
    }

    /** @test */
    public function saved_event_does_not_create_history_for_unchanged_fields(): void
    {
        $product = Product::factory()->create([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 10,
        ]);

        // Re-fetch from DB to clear wasRecentlyCreated flag
        $product = Product::find($product->id);

        // Update only one field, leave others the same
        $product->pvp_proveedor = 120;
        $product->save();

        $histories = ProductPriceHistory::where('product_id', $product->id)->get();

        // Only pvp_proveedor should have a history record
        $this->assertCount(1, $histories);
        $this->assertEquals('pvp_proveedor', $histories->first()->field_changed);
    }

    /** @test */
    public function calculated_fields_update_when_editable_fields_change(): void
    {
        $product = Product::factory()->create([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 0,
            'coste_transporte' => 0,
            'iva_porcentaje' => 21,
            'desc_camion_vip' => 0,
            'desc_camion' => 0,
            'desc_oferta' => 0,
            'desc_vip' => 0,
            'desc_empresas' => 0,
            'desc_empresas_a' => 0,
        ]);

        $product->refresh();
        $oldPvp = $product->pvp;

        // Change discount
        $product->desc_prov_1 = 20;
        $product->save();
        $product->refresh();

        // PVP should have changed because coste_neto changed
        $this->assertNotEquals($oldPvp, $product->pvp);

        $expected = PriceCalculatorService::calculate([
            'pvp_proveedor' => 100,
            'desc_prov_1' => 20,
            'coste_transporte' => 0,
            'iva_porcentaje' => 21,
            'desc_camion_vip' => 0,
            'desc_camion' => 0,
            'desc_oferta' => 0,
            'desc_vip' => 0,
            'desc_empresas' => 0,
            'desc_empresas_a' => 0,
            'metros_articulo' => null,
        ]);

        foreach ($expected as $field => $value) {
            $this->assertEquals($value, $product->{$field}, "Field {$field} mismatch after update");
        }
    }
}
