<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductSpec;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\MaterialCalculatorService;
use App\Services\PdfGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: web-portal-product-catalog
 *
 * Property-based tests for PDF content generation.
 * Uses Faker with iteration loops to simulate PBT (100+ iterations).
 */
class PdfContentPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const PBT_ITERATIONS = 100;

    /**
     * Feature: web-portal-product-catalog, Property 11: PDF contains all required sections
     *
     * For any product with specs and any valid calculator result, the generated PDF
     * content should include: product name, technical specifications (peso, dimensiones),
     * materials breakdown table, subtotal, merma percentage, and final total.
     *
     * Strategy:
     * 1. Generate the quote PDF via PdfGeneratorService and verify it is non-empty
     *    binary content starting with %PDF.
     * 2. Render the Blade view directly to HTML and assert all required sections are present.
     *
     * **Validates: Requirements 5.2**
     */
    public function test_property11_pdf_contains_all_required_sections(): void
    {
        /** @var PdfGeneratorService $pdfService */
        $pdfService = app(PdfGeneratorService::class);

        /** @var MaterialCalculatorService $calcService */
        $calcService = app(MaterialCalculatorService::class);

        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // --- Build random product with specs and price ---
            $unit = Unit::factory()->create();
            $category = Category::factory()->create(['nivel' => 1, 'activo' => true]);
            $brand = Brand::factory()->create();
            $supplier = Supplier::factory()->create();

            $product = Product::factory()->create([
                'unidad_base_id' => $unit->id,
                'categoria_id' => $category->id,
                'marca_id' => $brand->id,
                'proveedor_principal_id' => $supplier->id,
                'visible_web' => true,
            ]);

            $pesoKg = fake()->randomFloat(3, 0.5, 50);
            $largoCm = fake()->randomFloat(2, 10, 300);
            $anchoCm = fake()->randomFloat(2, 10, 200);
            $m2PorUnidad = fake()->randomFloat(4, 0.1, 5);

            $spec = ProductSpec::factory()->create([
                'producto_id' => $product->id,
                'peso_kg' => $pesoKg,
                'largo_cm' => $largoCm,
                'ancho_cm' => $anchoCm,
                'm2_por_unidad' => $m2PorUnidad,
            ]);

            $customerType = CustomerType::factory()->create();
            $precioBase = fake()->randomFloat(2, 1, 500);
            ProductPrice::factory()->create([
                'producto_id' => $product->id,
                'tipo_cliente_id' => $customerType->id,
                'precio_base' => $precioBase,
                'activo' => true,
            ]);

            // Random m² and merma
            $m2 = fake()->randomFloat(2, 1, 200);
            $merma = fake()->randomFloat(1, 0, 30);

            // Calculate materials
            $calcResult = $calcService->calculate($product->fresh(), $m2, $merma);

            // ---- Part 1: PDF binary is valid ----
            $pdfContent = $pdfService->generateQuote($product->fresh(), $calcResult);

            $this->assertNotEmpty(
                $pdfContent,
                "Iteration {$i}: PDF content should not be empty"
            );
            $this->assertStringStartsWith(
                '%PDF',
                $pdfContent,
                "Iteration {$i}: PDF content should start with %PDF header"
            );

            // ---- Part 2: Blade view renders all required sections ----
            $product->loadMissing(['especificaciones', 'unidadBase', 'categoria', 'marca']);
            $description = $product->descripcion_larga_web ?: $product->descripcion;

            $html = view('pdf.quote', [
                'product' => $product,
                'specs' => $product->especificaciones,
                'description' => $description,
                'materiales' => $calcResult['materiales'],
                'subtotal' => $calcResult['subtotal_sin_merma'],
                'merma_porcentaje' => $calcResult['merma_porcentaje'],
                'total' => $calcResult['total_con_merma'],
                'category' => $product->categoria?->nombre ?? '',
                'brand' => $product->marca?->nombre ?? '',
                'unit' => $product->unidadBase?->abreviatura ?? 'ud',
            ])->render();

            // Product name
            $this->assertStringContainsString(
                $product->nombre,
                $html,
                "Iteration {$i}: PDF HTML should contain product name '{$product->nombre}'"
            );

            // Technical specs: peso
            $this->assertStringContainsString(
                (string) $spec->peso_kg,
                $html,
                "Iteration {$i}: PDF HTML should contain peso '{$spec->peso_kg}'"
            );
            $this->assertStringContainsString(
                'kg',
                $html,
                "Iteration {$i}: PDF HTML should contain 'kg' unit for peso"
            );

            // Technical specs: dimensiones (largo × ancho)
            $this->assertStringContainsString(
                (string) $spec->largo_cm,
                $html,
                "Iteration {$i}: PDF HTML should contain largo_cm '{$spec->largo_cm}'"
            );
            $this->assertStringContainsString(
                (string) $spec->ancho_cm,
                $html,
                "Iteration {$i}: PDF HTML should contain ancho_cm '{$spec->ancho_cm}'"
            );

            // Materials breakdown table headers
            $this->assertStringContainsString(
                'Desglose de Materiales',
                $html,
                "Iteration {$i}: PDF HTML should contain 'Desglose de Materiales' section"
            );

            // Each material row: descripcion present
            foreach ($calcResult['materiales'] as $material) {
                $this->assertStringContainsString(
                    $material['descripcion'],
                    $html,
                    "Iteration {$i}: PDF HTML should contain material descripcion '{$material['descripcion']}'"
                );
            }

            // Subtotal
            $subtotalFormatted = number_format($calcResult['subtotal_sin_merma'], 2, ',', '.');
            $this->assertStringContainsString(
                $subtotalFormatted,
                $html,
                "Iteration {$i}: PDF HTML should contain subtotal '{$subtotalFormatted}'"
            );

            // Merma percentage
            $mermaFormatted = number_format($calcResult['merma_porcentaje'], 1, ',', '.');
            $this->assertStringContainsString(
                $mermaFormatted,
                $html,
                "Iteration {$i}: PDF HTML should contain merma percentage '{$mermaFormatted}'"
            );

            // Final total
            $totalFormatted = number_format($calcResult['total_con_merma'], 2, ',', '.');
            $this->assertStringContainsString(
                $totalFormatted,
                $html,
                "Iteration {$i}: PDF HTML should contain total '{$totalFormatted}'"
            );
        }
    }
}
