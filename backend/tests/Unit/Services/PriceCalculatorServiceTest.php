<?php

namespace Tests\Unit\Services;

use App\Services\PriceCalculatorService;
use PHPUnit\Framework\TestCase;

class PriceCalculatorServiceTest extends TestCase
{
    public function test_basic_calculation_with_typical_inputs(): void
    {
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => 100.0,
            'desc_prov_1'     => 10.0,
            'coste_transporte' => 5.0,
            'iva_porcentaje'  => 21.0,
            'desc_camion_vip' => 30.0,
            'desc_camion'     => 25.0,
            'desc_oferta'     => 20.0,
            'desc_vip'        => 15.0,
            'desc_empresas'   => 10.0,
            'desc_empresas_a' => 5.0,
            'metros_articulo' => 2.5,
        ]);

        // coste_neto = 100 * (1 - 10/100) = 90
        $this->assertEquals(90.0, $result['coste_neto']);
        // coste_neto_m2 = 90 (metros_articulo is set)
        $this->assertEquals(90.0, $result['coste_neto_m2']);
        // coste_m2_trans = 90 + 5 = 95
        $this->assertEquals(95.0, $result['coste_m2_trans']);
        // pre_pvp = 90 * 1.25 = 112.5
        $this->assertEquals(112.5, $result['pre_pvp']);
        // pvp = 112.5 * 1.21 = 136.125
        $this->assertEquals(136.125, $result['pvp']);
        // neto_camion_vip = 136.125 * 0.70 = 95.2875
        $this->assertEquals(95.2875, $result['neto_camion_vip']);
        // neto_camion = 136.125 * 0.75 = 102.0938 (rounded to 4)
        $this->assertEquals(102.0938, $result['neto_camion']);
        // neto_oferta = 136.125 * 0.80 = 108.9
        $this->assertEquals(108.9, $result['neto_oferta']);
        // neto_vip = 136.125 * 0.85 = 115.7063 (rounded to 4)
        $this->assertEquals(115.7063, $result['neto_vip']);
        // neto_empresas = 136.125 * 0.90 = 122.5125
        $this->assertEquals(122.5125, $result['neto_empresas']);
        // neto_empresas_a = 136.125 * 0.95 = 129.3188 (rounded to 4)
        $this->assertEquals(129.3188, $result['neto_empresas_a']);
    }

    public function test_null_metros_articulo_returns_null_for_m2_fields(): void
    {
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => 50.0,
            'desc_prov_1'     => 0.0,
            'coste_transporte' => 3.0,
            'iva_porcentaje'  => 21.0,
            'desc_camion_vip' => 0.0,
            'desc_camion'     => 0.0,
            'desc_oferta'     => 0.0,
            'desc_vip'        => 0.0,
            'desc_empresas'   => 0.0,
            'desc_empresas_a' => 0.0,
            'metros_articulo' => null,
        ]);

        $this->assertNull($result['coste_neto_m2']);
        $this->assertNull($result['coste_m2_trans']);
        $this->assertEquals(50.0, $result['coste_neto']);
    }

    public function test_negative_pvp_proveedor_clamped_to_zero(): void
    {
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => -50.0,
            'desc_prov_1'     => 10.0,
            'coste_transporte' => 0.0,
            'iva_porcentaje'  => 21.0,
            'desc_camion_vip' => 0.0,
            'desc_camion'     => 0.0,
            'desc_oferta'     => 0.0,
            'desc_vip'        => 0.0,
            'desc_empresas'   => 0.0,
            'desc_empresas_a' => 0.0,
            'metros_articulo' => null,
        ]);

        $this->assertEquals(0.0, $result['coste_neto']);
        $this->assertEquals(0.0, $result['pre_pvp']);
        $this->assertEquals(0.0, $result['pvp']);
    }

    public function test_percentages_clamped_to_0_100(): void
    {
        // desc_prov_1 = 150 should be clamped to 100 → coste_neto = 0
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => 100.0,
            'desc_prov_1'     => 150.0,
            'coste_transporte' => 0.0,
            'iva_porcentaje'  => 21.0,
            'desc_camion_vip' => -10.0,
            'desc_camion'     => 0.0,
            'desc_oferta'     => 0.0,
            'desc_vip'        => 0.0,
            'desc_empresas'   => 0.0,
            'desc_empresas_a' => 0.0,
            'metros_articulo' => null,
        ]);

        // desc_prov_1 clamped to 100 → coste_neto = 100 * (1 - 1) = 0
        $this->assertEquals(0.0, $result['coste_neto']);
        $this->assertEquals(0.0, $result['pre_pvp']);
        $this->assertEquals(0.0, $result['pvp']);
        // desc_camion_vip clamped to 0 → neto_camion_vip = pvp * 1.0 = 0
        $this->assertEquals(0.0, $result['neto_camion_vip']);
    }

    public function test_zero_discounts_pass_through(): void
    {
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => 200.0,
            'desc_prov_1'     => 0.0,
            'coste_transporte' => 0.0,
            'iva_porcentaje'  => 0.0,
            'desc_camion_vip' => 0.0,
            'desc_camion'     => 0.0,
            'desc_oferta'     => 0.0,
            'desc_vip'        => 0.0,
            'desc_empresas'   => 0.0,
            'desc_empresas_a' => 0.0,
            'metros_articulo' => null,
        ]);

        // coste_neto = 200, pre_pvp = 250, pvp = 250 (0% IVA)
        $this->assertEquals(200.0, $result['coste_neto']);
        $this->assertEquals(250.0, $result['pre_pvp']);
        $this->assertEquals(250.0, $result['pvp']);
        // All netos equal pvp with 0% discount
        $this->assertEquals(250.0, $result['neto_camion_vip']);
        $this->assertEquals(250.0, $result['neto_camion']);
        $this->assertEquals(250.0, $result['neto_oferta']);
        $this->assertEquals(250.0, $result['neto_vip']);
        $this->assertEquals(250.0, $result['neto_empresas']);
        $this->assertEquals(250.0, $result['neto_empresas_a']);
    }

    public function test_all_results_rounded_to_4_decimals(): void
    {
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => 33.3333,
            'desc_prov_1'     => 7.77,
            'coste_transporte' => 1.1111,
            'iva_porcentaje'  => 21.0,
            'desc_camion_vip' => 13.33,
            'desc_camion'     => 0.0,
            'desc_oferta'     => 0.0,
            'desc_vip'        => 0.0,
            'desc_empresas'   => 0.0,
            'desc_empresas_a' => 0.0,
            'metros_articulo' => 1.5,
        ]);

        // Verify all numeric values are rounded to 4 decimals
        foreach ($result as $key => $value) {
            if ($value !== null) {
                $parts = explode('.', (string) $value);
                $decimals = isset($parts[1]) ? strlen($parts[1]) : 0;
                $this->assertLessThanOrEqual(4, $decimals, "Field $key has more than 4 decimals");
            }
        }
    }

    public function test_missing_inputs_default_to_zero(): void
    {
        $result = PriceCalculatorService::calculate([]);

        $this->assertEquals(0.0, $result['coste_neto']);
        $this->assertEquals(0.0, $result['pre_pvp']);
        $this->assertEquals(0.0, $result['pvp']);
        $this->assertNull($result['coste_neto_m2']);
        $this->assertNull($result['coste_m2_trans']);
    }

    public function test_hundred_percent_discount_yields_zero_neto(): void
    {
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => 100.0,
            'desc_prov_1'     => 0.0,
            'coste_transporte' => 0.0,
            'iva_porcentaje'  => 21.0,
            'desc_camion_vip' => 100.0,
            'desc_camion'     => 100.0,
            'desc_oferta'     => 100.0,
            'desc_vip'        => 100.0,
            'desc_empresas'   => 100.0,
            'desc_empresas_a' => 100.0,
            'metros_articulo' => null,
        ]);

        $this->assertEquals(0.0, $result['neto_camion_vip']);
        $this->assertEquals(0.0, $result['neto_camion']);
        $this->assertEquals(0.0, $result['neto_oferta']);
        $this->assertEquals(0.0, $result['neto_vip']);
        $this->assertEquals(0.0, $result['neto_empresas']);
        $this->assertEquals(0.0, $result['neto_empresas_a']);
    }

    public function test_negative_coste_transporte_clamped_to_zero(): void
    {
        $result = PriceCalculatorService::calculate([
            'pvp_proveedor'   => 100.0,
            'desc_prov_1'     => 0.0,
            'coste_transporte' => -10.0,
            'iva_porcentaje'  => 21.0,
            'desc_camion_vip' => 0.0,
            'desc_camion'     => 0.0,
            'desc_oferta'     => 0.0,
            'desc_vip'        => 0.0,
            'desc_empresas'   => 0.0,
            'desc_empresas_a' => 0.0,
            'metros_articulo' => 1.0,
        ]);

        // coste_neto_m2 = 100, coste_m2_trans = 100 + 0 = 100 (transport clamped to 0)
        $this->assertEquals(100.0, $result['coste_neto_m2']);
        $this->assertEquals(100.0, $result['coste_m2_trans']);
    }
}
