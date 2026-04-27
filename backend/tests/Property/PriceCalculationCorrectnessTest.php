<?php

namespace Tests\Property;

use App\Services\PriceCalculatorService;
use PHPUnit\Framework\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 1: Price Calculation Correctness
 *
 * Property-based test that generates random valid price inputs and verifies
 * all calculated fields match the design spec formulas.
 *
 * Validates: Requirements 4.2, 4.3, 4.4, 4.5, 4.6, 4.8
 */
class PriceCalculationCorrectnessTest extends TestCase
{
    private const ITERATIONS = 100;
    private const TOLERANCE = 1e-4;

    /**
     * Generate a random float between $min and $max.
     */
    private function randomFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    /**
     * Generate a random valid set of price inputs.
     */
    private function generateInputs(): array
    {
        return [
            'pvp_proveedor'    => $this->randomFloat(0, 1000),
            'desc_prov_1'      => $this->randomFloat(0, 100),
            'coste_transporte' => $this->randomFloat(0, 50),
            'iva_porcentaje'   => $this->randomFloat(0, 100),
            'desc_camion_vip'  => $this->randomFloat(0, 100),
            'desc_camion'      => $this->randomFloat(0, 100),
            'desc_oferta'      => $this->randomFloat(0, 100),
            'desc_vip'         => $this->randomFloat(0, 100),
            'desc_empresas'    => $this->randomFloat(0, 100),
            'desc_empresas_a'  => $this->randomFloat(0, 100),
            'metros_articulo'  => mt_rand(0, 1) === 1 ? $this->randomFloat(0.1, 10) : null,
        ];
    }

    /**
     * Independently compute expected values using the design spec formulas.
     */
    private function computeExpected(array $inputs): array
    {
        $pvpProveedor    = max(0, (float) ($inputs['pvp_proveedor'] ?? 0));
        $descProv1       = max(0, min(100, (float) ($inputs['desc_prov_1'] ?? 0)));
        $costeTransporte = max(0, (float) ($inputs['coste_transporte'] ?? 0));
        $ivaPorcentaje   = max(0, min(100, (float) ($inputs['iva_porcentaje'] ?? 0)));
        $descCamionVip   = max(0, min(100, (float) ($inputs['desc_camion_vip'] ?? 0)));
        $descCamion      = max(0, min(100, (float) ($inputs['desc_camion'] ?? 0)));
        $descOferta      = max(0, min(100, (float) ($inputs['desc_oferta'] ?? 0)));
        $descVip         = max(0, min(100, (float) ($inputs['desc_vip'] ?? 0)));
        $descEmpresas    = max(0, min(100, (float) ($inputs['desc_empresas'] ?? 0)));
        $descEmpresasA   = max(0, min(100, (float) ($inputs['desc_empresas_a'] ?? 0)));
        $metrosArticulo  = $inputs['metros_articulo'] ?? null;

        // Requirement 4.2: coste_neto = pvp_proveedor × (1 − desc_prov_1 / 100)
        $costeNeto = $pvpProveedor * (1 - $descProv1 / 100);

        // Requirement 4.8: coste_neto_m2 and coste_m2_trans
        $costeNetoM2  = ($metrosArticulo !== null) ? $costeNeto : null;
        $costeM2Trans = ($costeNetoM2 !== null) ? $costeNetoM2 + $costeTransporte : null;

        // Requirement 4.3: pre_pvp = coste_neto × 1.25
        $prePvp = $costeNeto * 1.25;

        // Requirement 4.4: pvp = pre_pvp × (1 + iva_porcentaje / 100)
        $pvp = $prePvp * (1 + $ivaPorcentaje / 100);

        // Requirement 4.5: neto_X = pvp × (1 − desc_X / 100)
        return [
            'coste_neto'      => round($costeNeto, 4),
            'coste_neto_m2'   => $costeNetoM2 !== null ? round($costeNetoM2, 4) : null,
            'coste_m2_trans'  => $costeM2Trans !== null ? round($costeM2Trans, 4) : null,
            'pre_pvp'         => round($prePvp, 4),
            'pvp'             => round($pvp, 4),
            'neto_camion_vip' => round($pvp * (1 - $descCamionVip / 100), 4),
            'neto_camion'     => round($pvp * (1 - $descCamion / 100), 4),
            'neto_oferta'     => round($pvp * (1 - $descOferta / 100), 4),
            'neto_vip'        => round($pvp * (1 - $descVip / 100), 4),
            'neto_empresas'   => round($pvp * (1 - $descEmpresas / 100), 4),
            'neto_empresas_a' => round($pvp * (1 - $descEmpresasA / 100), 4),
        ];
    }

    /**
     * @test
     * Property 1: Price Calculation Correctness
     *
     * For any valid set of price inputs, calculatePrices SHALL produce results
     * matching the design spec formulas within floating-point tolerance.
     *
     * Validates: Requirements 4.2, 4.3, 4.4, 4.5, 4.6, 4.8
     */
    public function all_calculated_fields_match_design_spec_formulas(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $inputs   = $this->generateInputs();
            $actual   = PriceCalculatorService::calculate($inputs);
            $expected = $this->computeExpected($inputs);

            $fields = [
                'coste_neto', 'pre_pvp', 'pvp',
                'neto_camion_vip', 'neto_camion', 'neto_oferta',
                'neto_vip', 'neto_empresas', 'neto_empresas_a',
            ];

            foreach ($fields as $field) {
                $this->assertEqualsWithDelta(
                    $expected[$field],
                    $actual[$field],
                    self::TOLERANCE,
                    "Iteration $i: Field '$field' mismatch. Inputs: " . json_encode($inputs)
                );
            }

            // coste_neto_m2 and coste_m2_trans nullable checks
            if ($expected['coste_neto_m2'] === null) {
                $this->assertNull($actual['coste_neto_m2'], "Iteration $i: coste_neto_m2 should be null");
                $this->assertNull($actual['coste_m2_trans'], "Iteration $i: coste_m2_trans should be null");
            } else {
                $this->assertEqualsWithDelta(
                    $expected['coste_neto_m2'],
                    $actual['coste_neto_m2'],
                    self::TOLERANCE,
                    "Iteration $i: coste_neto_m2 mismatch"
                );
                $this->assertEqualsWithDelta(
                    $expected['coste_m2_trans'],
                    $actual['coste_m2_trans'],
                    self::TOLERANCE,
                    "Iteration $i: coste_m2_trans mismatch"
                );
            }
        }
    }

    /**
     * @test
     * Validates: Requirements 4.2, 4.3, 4.4, 4.5
     *
     * All non-nullable numeric outputs are always >= 0 for valid inputs.
     */
    public function all_non_nullable_outputs_are_non_negative(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $inputs = $this->generateInputs();
            $result = PriceCalculatorService::calculate($inputs);

            $this->assertGreaterThanOrEqual(0, $result['coste_neto'],
                "Iteration $i: coste_neto must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['pre_pvp'],
                "Iteration $i: pre_pvp must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['pvp'],
                "Iteration $i: pvp must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['neto_camion_vip'],
                "Iteration $i: neto_camion_vip must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['neto_camion'],
                "Iteration $i: neto_camion must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['neto_oferta'],
                "Iteration $i: neto_oferta must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['neto_vip'],
                "Iteration $i: neto_vip must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['neto_empresas'],
                "Iteration $i: neto_empresas must be >= 0. Inputs: " . json_encode($inputs));
            $this->assertGreaterThanOrEqual(0, $result['neto_empresas_a'],
                "Iteration $i: neto_empresas_a must be >= 0. Inputs: " . json_encode($inputs));
        }
    }

    /**
     * @test
     * Validates: Requirements 4.8
     *
     * When metros_articulo is null, coste_neto_m2 and coste_m2_trans are null.
     * When metros_articulo is not null, coste_neto_m2 equals coste_neto and
     * coste_m2_trans equals coste_neto_m2 + coste_transporte.
     */
    public function metros_articulo_null_handling_is_correct(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $inputs = $this->generateInputs();
            $result = PriceCalculatorService::calculate($inputs);

            if ($inputs['metros_articulo'] === null) {
                $this->assertNull($result['coste_neto_m2'],
                    "Iteration $i: coste_neto_m2 should be null when metros_articulo is null");
                $this->assertNull($result['coste_m2_trans'],
                    "Iteration $i: coste_m2_trans should be null when metros_articulo is null");
            } else {
                $this->assertEqualsWithDelta(
                    $result['coste_neto'],
                    $result['coste_neto_m2'],
                    self::TOLERANCE,
                    "Iteration $i: coste_neto_m2 should equal coste_neto when metros_articulo is set"
                );

                $expectedM2Trans = $result['coste_neto_m2'] + max(0, (float) $inputs['coste_transporte']);
                $this->assertEqualsWithDelta(
                    $expectedM2Trans,
                    $result['coste_m2_trans'],
                    self::TOLERANCE,
                    "Iteration $i: coste_m2_trans should equal coste_neto_m2 + coste_transporte"
                );
            }
        }
    }
}
