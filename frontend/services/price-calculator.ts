import type { PriceInputs, PriceOutputs } from './types';

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}

function round4(value: number): number {
  return Math.round(value * 10000) / 10000;
}

export function calculatePrices(input: PriceInputs): PriceOutputs {
  const pvp_proveedor = Math.max(input.pvp_proveedor ?? 0, 0);
  const desc_prov_1 = clamp(input.desc_prov_1 ?? 0, 0, 100);
  const coste_transporte = Math.max(input.coste_transporte ?? 0, 0);
  const iva_porcentaje = clamp(input.iva_porcentaje ?? 0, 0, 100);
  const desc_camion_vip = clamp(input.desc_camion_vip ?? 0, 0, 100);
  const desc_camion = clamp(input.desc_camion ?? 0, 0, 100);
  const desc_oferta = clamp(input.desc_oferta ?? 0, 0, 100);
  const desc_vip = clamp(input.desc_vip ?? 0, 0, 100);
  const desc_empresas = clamp(input.desc_empresas ?? 0, 0, 100);
  const desc_empresas_a = clamp(input.desc_empresas_a ?? 0, 0, 100);

  const coste_neto = round4(Math.max(pvp_proveedor * (1 - desc_prov_1 / 100), 0));

  const coste_neto_m2 = input.metros_articulo != null ? coste_neto : null;
  const coste_m2_trans = coste_neto_m2 != null
    ? round4(Math.max(coste_neto_m2 + coste_transporte, 0))
    : null;

  const pre_pvp = round4(Math.max(coste_neto * 1.25, 0));
  const pvp = round4(Math.max(pre_pvp * (1 + iva_porcentaje / 100), 0));

  return {
    coste_neto,
    coste_neto_m2,
    coste_m2_trans,
    pre_pvp,
    pvp,
    neto_camion_vip: round4(Math.max(pvp * (1 - desc_camion_vip / 100), 0)),
    neto_camion: round4(Math.max(pvp * (1 - desc_camion / 100), 0)),
    neto_oferta: round4(Math.max(pvp * (1 - desc_oferta / 100), 0)),
    neto_vip: round4(Math.max(pvp * (1 - desc_vip / 100), 0)),
    neto_empresas: round4(Math.max(pvp * (1 - desc_empresas / 100), 0)),
    neto_empresas_a: round4(Math.max(pvp * (1 - desc_empresas_a / 100), 0)),
  };
}
