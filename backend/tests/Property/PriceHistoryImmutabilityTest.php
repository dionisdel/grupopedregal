<?php

namespace Tests\Property;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 8: Price History Immutability
 *
 * Property-based test that creates a product with price history, soft-deletes
 * the product, and verifies history records remain unchanged.
 *
 * **Validates: Requirements 6.4**
 */
class PriceHistoryImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 30;

    /**
     * Generate a random float between $min and $max.
     */
    private function randomFloat(float $min, float $max, int $decimals = 4): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
    }

    /**
     * @test
     * Property 8: Price History Immutability
     *
     * For any product that has price history records, soft-deleting the product
     * SHALL NOT remove or modify any existing product_price_history records.
     * The count and content of history records SHALL remain identical before
     * and after deletion.
     *
     * Validates: Requirements 6.4
     */
    public function soft_delete_does_not_affect_price_history(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // 1. Create a product
            $category = Category::factory()->create();
            $product = Product::factory()->create([
                'categoria_id' => $category->id,
            ]);

            // Re-fetch to clear wasRecentlyCreated
            $product = Product::find($product->id);

            // 2. Make 1-3 random price changes to generate history
            $numChanges = mt_rand(1, 3);
            for ($c = 0; $c < $numChanges; $c++) {
                $fieldsToChange = [];
                $editableFields = Product::EDITABLE_PRICE_FIELDS;
                $numFields = mt_rand(1, 3);
                shuffle($editableFields);
                $selectedFields = array_slice($editableFields, 0, $numFields);

                foreach ($selectedFields as $field) {
                    $oldVal = (float) $product->{$field};
                    if (in_array($field, ['pvp_proveedor', 'coste_transporte'])) {
                        $fieldsToChange[$field] = round($oldVal + $this->randomFloat(5, 50), 4);
                    } else {
                        $newVal = round(fmod($oldVal + $this->randomFloat(5, 30, 2), 100), 2);
                        if ($newVal < 0) $newVal += 100;
                        if ((float) $newVal === $oldVal) $newVal = round($oldVal + 10, 2);
                        $fieldsToChange[$field] = $newVal;
                    }
                }

                $product->update($fieldsToChange);
                $product = Product::find($product->id); // re-fetch
            }

            // 3. Capture history records BEFORE soft-delete
            $historyBefore = ProductPriceHistory::where('product_id', $product->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id,
                    'product_id' => $h->product_id,
                    'field_changed' => $h->field_changed,
                    'old_value' => $h->old_value,
                    'new_value' => $h->new_value,
                    'changed_at' => (string) $h->changed_at,
                ])
                ->toArray();

            $countBefore = count($historyBefore);

            $this->assertGreaterThan(
                0,
                $countBefore,
                "Iteration $i: Product should have at least one history record before deletion"
            );

            // 4. Soft-delete the product
            $product->delete();

            // Verify product is soft-deleted
            $this->assertSoftDeleted('products', ['id' => $product->id]);

            // 5. Verify history records are unchanged after soft-delete
            $historyAfter = ProductPriceHistory::where('product_id', $product->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id,
                    'product_id' => $h->product_id,
                    'field_changed' => $h->field_changed,
                    'old_value' => $h->old_value,
                    'new_value' => $h->new_value,
                    'changed_at' => (string) $h->changed_at,
                ])
                ->toArray();

            $this->assertCount(
                $countBefore,
                $historyAfter,
                "Iteration $i: History count should be $countBefore after soft-delete, got " . count($historyAfter)
            );

            $this->assertEquals(
                $historyBefore,
                $historyAfter,
                "Iteration $i: History records should be identical before and after soft-delete"
            );

            // Clean up
            ProductPriceHistory::where('product_id', $product->id)->delete();
            $product->forceDelete();
            $category->forceDelete();
        }
    }
}
