<?php

namespace Tests\Property;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ExcelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 12: Excel Import Correctness
 *
 * For any set of Excel rows:
 * - Rows with a codigo_articulo that already exists SHALL update the existing product
 * - Rows with a new codigo_articulo SHALL create a new product
 * - Rows with invalid/missing required data SHALL be reported as errors with row number
 * - created + updated + errors SHALL equal total data rows
 *
 * **Validates: Requirements 10.8**
 */
class ExcelImportCorrectnessTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 50;

    private ExcelImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExcelImportService();
    }

    /**
     * Generate a random valid row of import data.
     */
    private function generateValidRow(int $index): array
    {
        $code = 'ART-TEST-' . str_pad((string) $index, 5, '0', STR_PAD_LEFT);
        return [
            'COD_ARTICULO'           => $code,
            'DESCRIPCION'            => 'Producto de prueba ' . $index,
            'COD_PROVEEDOR'          => 'PROV-' . ($index % 5),
            'PROVEEDOR'              => 'Proveedor ' . ($index % 5),
            'COD_ARTICULO_PROVEEDOR' => 'PAP-' . $index,
            'MARCA'                  => 'Marca ' . ($index % 3),
            'KG_LITRO'               => round(mt_rand(1, 500) / 10, 1),
            'LARGO'                  => round(mt_rand(10, 200) / 10, 1),
            'ANCHO'                  => round(mt_rand(10, 200) / 10, 1),
            'METROS_ARTICULO'        => mt_rand(0, 1) ? round(mt_rand(1, 100) / 10, 2) : null,
            'UNIDADES_POR_ARTICULO'  => mt_rand(1, 50),
            'ARTICULOS_POR_EMBALAJE' => mt_rand(1, 20),
            'UNIDADES_PALET'         => mt_rand(10, 500),
            'PALET_RETORNABLE'       => mt_rand(0, 1) ? 'SI' : 'NO',
            'PVP_PROVEEDOR'          => round(mt_rand(100, 50000) / 100, 2),
            'DESC_PROV_1'            => round(mt_rand(0, 5000) / 100, 2),
            'COSTE_TRANSPORTE'       => round(mt_rand(0, 2000) / 100, 2),
            'IVA'                    => 21,
            'DESC_CAMION_VIP'        => round(mt_rand(0, 5000) / 100, 2),
            'DESC_CAMION'            => round(mt_rand(0, 5000) / 100, 2),
            'DESC_OFERTA'            => round(mt_rand(0, 5000) / 100, 2),
            'DESC_VIP'               => round(mt_rand(0, 5000) / 100, 2),
            'DESC_EMPRESAS'          => round(mt_rand(0, 5000) / 100, 2),
            'DESC_EMPRESAS_A'        => round(mt_rand(0, 5000) / 100, 2),
            'COD_FAMILIA'            => 'FAM-' . ($index % 4),
            'FAMILIA'                => 'Familia ' . ($index % 4),
            'COD_SUBFAMILIA'         => 'SUB-' . ($index % 8),
            'SUBFAMILIA'             => 'Subfamilia ' . ($index % 8),
        ];
    }

    /**
     * Generate an invalid row (missing required fields).
     */
    private function generateInvalidRow(): array
    {
        return [
            'COD_ARTICULO' => '',
            'DESCRIPCION'  => '',
        ];
    }

    /**
     * @test
     * Property 12: created + updated + errors = total rows
     */
    public function created_plus_updated_plus_errors_equals_total_rows(): void
    {
        for ($iter = 0; $iter < self::ITERATIONS; $iter++) {
            // Generate a mix of valid and invalid rows
            $totalRows = mt_rand(3, 15);
            $rows = [];
            $invalidCount = mt_rand(0, min(3, $totalRows - 1));

            for ($i = 0; $i < $totalRows - $invalidCount; $i++) {
                $rows[] = $this->generateValidRow($iter * 100 + $i);
            }
            for ($i = 0; $i < $invalidCount; $i++) {
                $rows[] = $this->generateInvalidRow();
            }

            // Shuffle to randomize order
            shuffle($rows);

            $result = $this->service->importFromRows($rows);

            $total = $result['created'] + $result['updated'] + count($result['errors']);

            $this->assertEquals(
                $totalRows,
                $total,
                "Iteration $iter: created({$result['created']}) + updated({$result['updated']}) + errors(" . count($result['errors']) . ") = $total, expected $totalRows"
            );
        }
    }

    /**
     * @test
     * Property 12: Upsert behavior — same codigo_articulo updates, new creates
     */
    public function upsert_updates_existing_and_creates_new(): void
    {
        for ($iter = 0; $iter < self::ITERATIONS; $iter++) {
            // First import: create some products
            $rowCount = mt_rand(2, 6);
            $rows = [];
            for ($i = 0; $i < $rowCount; $i++) {
                $rows[] = $this->generateValidRow($iter * 1000 + $i);
            }

            $firstResult = $this->service->importFromRows($rows);
            $this->assertEquals($rowCount, $firstResult['created'], "Iteration $iter: first import should create all");
            $this->assertEquals(0, $firstResult['updated'], "Iteration $iter: first import should update none");

            // Second import: re-import same rows (should update, not create)
            // Modify descriptions to confirm update
            foreach ($rows as &$row) {
                $row['DESCRIPCION'] = 'Updated ' . $row['DESCRIPCION'];
            }
            unset($row);

            $secondResult = $this->service->importFromRows($rows);
            $this->assertEquals(0, $secondResult['created'], "Iteration $iter: second import should create none");
            $this->assertEquals($rowCount, $secondResult['updated'], "Iteration $iter: second import should update all");

            // Verify the descriptions were actually updated
            foreach ($rows as $row) {
                $product = Product::where('codigo_articulo', $row['COD_ARTICULO'])->first();
                $this->assertNotNull($product, "Iteration $iter: product should exist");
                $this->assertStringStartsWith('Updated', $product->descripcion, "Iteration $iter: description should be updated");
            }
        }
    }

    /**
     * @test
     * Property 12: Invalid rows are reported as errors with row numbers
     */
    public function invalid_rows_reported_as_errors_with_row_numbers(): void
    {
        $rows = [
            $this->generateValidRow(1),
            $this->generateInvalidRow(), // row index 1 → row number 3 (header=1, data starts at 2)
            $this->generateValidRow(2),
        ];

        $result = $this->service->importFromRows($rows);

        $this->assertEquals(2, $result['created']);
        $this->assertCount(1, $result['errors']);
        // Error row number should be index+2 (header offset)
        $this->assertEquals(3, $result['errors'][0]['row']);
        $this->assertNotEmpty($result['errors'][0]['message']);
    }
}
