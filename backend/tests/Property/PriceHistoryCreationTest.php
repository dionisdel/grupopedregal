<?php

namespace Tests\Property;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 7: Price History Creation on Change
 *
 * Property-based test that modifies random editable price fields on a product
 * and verifies that ProductPriceHistory records are created correctly for each
 * changed field, with correct old_value, new_value, and changed_at.
 *
 * Validates: Requirements 6.2
 */
class PriceHistoryCreationTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 30;

    /**
     * Generate a random float between $min and $max rounded to $decimals.
     */
    private function randomFloat(float $min, float $max, int $decimals = 4): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
    }

    /**
     * @test
     * Property 7: Price History Creation on Change
     *
     * For any product and any modification to editable price fields,
     * the system SHALL create a product_price_history record with the correct
     * field_changed, old_value, new_value, and changed_at for each changed field.
     * No history records SHALL exist for unchanged fields.
     *
     * Validates: Requirements 6.2
     */
    public function history_records_created_for_each_changed_price_field(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // 1. Create a product with known initial editable price values
            $category = Category::factory()->create();
            $product = Product::factory()->create([
                'categoria_id' => $category->id,
            ]);

            // Capture the initial values after creation
            // Re-fetch from DB to clear wasRecentlyCreated flag
            $product = Product::find($product->id);
            $initialValues = [];
            foreach (Product::EDITABLE_PRICE_FIELDS as $field) {
                $initialValues[$field] = (float) $product->{$field};
            }

            // 2. Randomly select 1-5 editable price fields to change
            $allFields = Product::EDITABLE_PRICE_FIELDS;
            $numFieldsToChange = mt_rand(1, 5);
            $shuffled = $allFields;
            shuffle($shuffled);
            $fieldsToChange = array_slice($shuffled, 0, $numFieldsToChange);
            $unchangedFields = array_diff($allFields, $fieldsToChange);

            // 3. Generate new random values for those fields (ensure they differ from old)
            $newValues = [];
            foreach ($fieldsToChange as $field) {
                $oldVal = $initialValues[$field];
                // Generate a value guaranteed to be different by offsetting from old
                if (in_array($field, ['pvp_proveedor', 'coste_transporte'])) {
                    // Offset by a random amount between 5 and 100
                    $newVal = round($oldVal + $this->randomFloat(5, 100), 4);
                } else {
                    // Percentage fields: shift by 5-40 and wrap within 0-100
                    $offset = $this->randomFloat(5, 40, 2);
                    $newVal = round(fmod($oldVal + $offset, 100), 2);
                    if ($newVal < 0) {
                        $newVal += 100;
                    }
                }
                // Final safety: if still equal, just add 10
                if ((float) $newVal === (float) $oldVal) {
                    $newVal = round($oldVal + 10, 4);
                }
                $newValues[$field] = $newVal;
            }

            // Clear any existing history (from creation — though creation shouldn't create history)
            ProductPriceHistory::where('product_id', $product->id)->delete();

            // 4. Update the product with the new values
            $product->update($newValues);

            // 5. Assert: a ProductPriceHistory record exists for each changed field
            foreach ($fieldsToChange as $field) {
                $historyRecord = ProductPriceHistory::where('product_id', $product->id)
                    ->where('field_changed', $field)
                    ->first();

                $this->assertNotNull(
                    $historyRecord,
                    "Iteration $i: Expected history record for field '$field' but none found. "
                    . "Changed fields: " . implode(', ', $fieldsToChange)
                );

                // 6. Assert: each history record has correct old_value and new_value
                $this->assertEqualsWithDelta(
                    $initialValues[$field],
                    (float) $historyRecord->old_value,
                    0.01,
                    "Iteration $i: old_value mismatch for field '$field'. "
                    . "Expected {$initialValues[$field]}, got {$historyRecord->old_value}"
                );

                $this->assertEqualsWithDelta(
                    $newValues[$field],
                    (float) $historyRecord->new_value,
                    0.01,
                    "Iteration $i: new_value mismatch for field '$field'. "
                    . "Expected {$newValues[$field]}, got {$historyRecord->new_value}"
                );

                // 8. Assert: changed_at is set
                $this->assertNotNull(
                    $historyRecord->changed_at,
                    "Iteration $i: changed_at should be set for field '$field'"
                );
            }

            // 7. Assert: no history records exist for unchanged fields
            foreach ($unchangedFields as $field) {
                $unexpectedRecord = ProductPriceHistory::where('product_id', $product->id)
                    ->where('field_changed', $field)
                    ->first();

                $this->assertNull(
                    $unexpectedRecord,
                    "Iteration $i: Unexpected history record for unchanged field '$field'. "
                    . "Unchanged fields should not have history records."
                );
            }

            // Clean up for next iteration
            ProductPriceHistory::where('product_id', $product->id)->delete();
            $product->forceDelete();
            $category->forceDelete();
        }
    }
}
