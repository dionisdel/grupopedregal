<?php

namespace App\Services;

/**
 * Pure price calculator for product pricing.
 *
 * Takes editable price inputs and returns all calculated fields.
 * Clamps percentages to 0–100, treats negative pvp_proveedor as 0,
 * and rounds all numeric results to 4 decimal places.
 *
 * @see Requirements 4.2, 4.3, 4.4, 4.5, 4.8
 */
class PriceCalculatorService
{
    /**
     * Calculate all derived price fields from editable inputs.
     *
     * @param array $inputs Associative array with keys:
     *   - pvp_proveedor (float)
     *   - desc_prov_1 (float) — % descuento proveedor
     *   - coste_transporte (float)
     *   - iva_porcentaje (float)
     *   - desc_camion_vip (float)
     *   - desc_camion (float)
     *   - desc_oferta (float)
     *   - desc_vip (float)
     *   - desc_empresas (float)
     *   - desc_empresas_a (float)
     *   - metros_articulo (float|null)
     *
     * @return array Associative array with calculated fields:
     *   coste_neto, coste_neto_m2, coste_m2_trans, pre_pvp, pvp,
     *   neto_camion_vip, neto_camion, neto_oferta, neto_vip,
     *   neto_empresas, neto_empresas_a
     */
    public static function calculate(array $inputs): array
    {
        // Extract inputs with defaults
        $pvpProveedor    = (float) ($inputs['pvp_proveedor'] ?? 0);
        $descProv1       = (float) ($inputs['desc_prov_1'] ?? 0);
        $costeTransporte = (float) ($inputs['coste_transporte'] ?? 0);
        $ivaPorcentaje   = (float) ($inputs['iva_porcentaje'] ?? 0);
        $descCamionVip   = (float) ($inputs['desc_camion_vip'] ?? 0);
        $descCamion      = (float) ($inputs['desc_camion'] ?? 0);
        $descOferta      = (float) ($inputs['desc_oferta'] ?? 0);
        $descVip         = (float) ($inputs['desc_vip'] ?? 0);
        $descEmpresas    = (float) ($inputs['desc_empresas'] ?? 0);
        $descEmpresasA   = (float) ($inputs['desc_empresas_a'] ?? 0);
        $metrosArticulo  = $inputs['metros_articulo'] ?? null;

        // Clamp: pvp_proveedor and coste_transporte cannot be negative
        $pvpProveedor    = max(0, $pvpProveedor);
        $costeTransporte = max(0, $costeTransporte);

        // Clamp all percentages to 0–100
        $descProv1     = self::clampPercentage($descProv1);
        $ivaPorcentaje = self::clampPercentage($ivaPorcentaje);
        $descCamionVip = self::clampPercentage($descCamionVip);
        $descCamion    = self::clampPercentage($descCamion);
        $descOferta    = self::clampPercentage($descOferta);
        $descVip       = self::clampPercentage($descVip);
        $descEmpresas  = self::clampPercentage($descEmpresas);
        $descEmpresasA = self::clampPercentage($descEmpresasA);

        // Calculate derived fields
        $costeNeto   = $pvpProveedor * (1 - $descProv1 / 100);
        $costeNetoM2 = ($metrosArticulo !== null) ? $costeNeto : null;
        $costeM2Trans = ($costeNetoM2 !== null) ? $costeNetoM2 + $costeTransporte : null;
        $prePvp      = $costeNeto * 1.25;
        $pvp         = $prePvp * (1 + $ivaPorcentaje / 100);

        $netoCamionVip = $pvp * (1 - $descCamionVip / 100);
        $netoCamion    = $pvp * (1 - $descCamion / 100);
        $netoOferta    = $pvp * (1 - $descOferta / 100);
        $netoVip       = $pvp * (1 - $descVip / 100);
        $netoEmpresas  = $pvp * (1 - $descEmpresas / 100);
        $netoEmpresasA = $pvp * (1 - $descEmpresasA / 100);

        // Round all numeric results to 4 decimal places
        return [
            'coste_neto'      => round($costeNeto, 4),
            'coste_neto_m2'   => $costeNetoM2 !== null ? round($costeNetoM2, 4) : null,
            'coste_m2_trans'  => $costeM2Trans !== null ? round($costeM2Trans, 4) : null,
            'pre_pvp'         => round($prePvp, 4),
            'pvp'             => round($pvp, 4),
            'neto_camion_vip' => round($netoCamionVip, 4),
            'neto_camion'     => round($netoCamion, 4),
            'neto_oferta'     => round($netoOferta, 4),
            'neto_vip'        => round($netoVip, 4),
            'neto_empresas'   => round($netoEmpresas, 4),
            'neto_empresas_a' => round($netoEmpresasA, 4),
        ];
    }

    /**
     * Clamp a value to the 0–100 range.
     */
    private static function clampPercentage(float $value): float
    {
        return max(0, min(100, $value));
    }
}
