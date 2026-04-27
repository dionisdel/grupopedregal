/**
 * Property 1: Price Calculation Correctness (frontend)
 * Feature: portal-ecommerce-v2, Property 1: Price Calculation Correctness
 *
 * Validates: Requirements 4.2, 4.3, 4.4, 4.5, 4.8
 *
 * For any valid set of price inputs, calculatePrices SHALL produce outputs
 * matching the design formulas exactly.
 */
import { describe, it, expect } from 'vitest';
import fc from 'fast-check';
import { calculatePrices } from '../../services/price-calculator';
import type { PriceInputs } from '../../services/types';

function round4(v: number): number {
  return Math.round(v * 10000) / 10000;
}

/** Arbitrary that generates valid PriceInputs within spec ranges */
const priceInputsArb: fc.Arbitrary<PriceInputs> = fc.record({
  pvp_proveedor: fc.double({ min: 0, max: 100_000, noNaN: true, noDefaultInfinity: true }),
  desc_prov_1: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  coste_transporte: fc.double({ min: 0, max: 10_000, noNaN: true, noDefaultInfinity: true }),
  iva_porcentaje: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  desc_camion_vip: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  desc_camion: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  desc_oferta: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  desc_vip: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  desc_empresas: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  desc_empresas_a: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
  metros_articulo: fc.oneof(
    fc.constant(null),
    fc.double({ min: 0.01, max: 1000, noNaN: true, noDefaultInfinity: true }),
  ),
});

describe('Property 1: Price Calculation Correctness (frontend)', () => {
  it('coste_neto = pvp_proveedor × (1 − desc_prov_1 / 100)', () => {
    fc.assert(
      fc.property(priceInputsArb, (input) => {
        const result = calculatePrices(input);
        const expected = round4(Math.max(input.pvp_proveedor * (1 - input.desc_prov_1 / 100), 0));
        expect(result.coste_neto).toBeCloseTo(expected, 4);
      }),
      { numRuns: 200 },
    );
  });

  it('pre_pvp = coste_neto × 1.25', () => {
    fc.assert(
      fc.property(priceInputsArb, (input) => {
        const result = calculatePrices(input);
        const expected = round4(Math.max(result.coste_neto * 1.25, 0));
        expect(result.pre_pvp).toBeCloseTo(expected, 4);
      }),
      { numRuns: 200 },
    );
  });

  it('pvp = pre_pvp × (1 + iva_porcentaje / 100)', () => {
    fc.assert(
      fc.property(priceInputsArb, (input) => {
        const result = calculatePrices(input);
        const expected = round4(Math.max(result.pre_pvp * (1 + input.iva_porcentaje / 100), 0));
        expect(result.pvp).toBeCloseTo(expected, 4);
      }),
      { numRuns: 200 },
    );
  });

  it('neto_X = pvp × (1 − desc_X / 100) for each discount type', () => {
    const discountFields = [
      'desc_camion_vip',
      'desc_camion',
      'desc_oferta',
      'desc_vip',
      'desc_empresas',
      'desc_empresas_a',
    ] as const;

    const netoFields = [
      'neto_camion_vip',
      'neto_camion',
      'neto_oferta',
      'neto_vip',
      'neto_empresas',
      'neto_empresas_a',
    ] as const;

    fc.assert(
      fc.property(priceInputsArb, (input) => {
        const result = calculatePrices(input);
        for (let i = 0; i < discountFields.length; i++) {
          const disc = input[discountFields[i]];
          const expected = round4(Math.max(result.pvp * (1 - disc / 100), 0));
          expect(result[netoFields[i]]).toBeCloseTo(expected, 4);
        }
      }),
      { numRuns: 200 },
    );
  });

  it('metros_articulo set → coste_neto_m2 = coste_neto, coste_m2_trans = coste_neto_m2 + coste_transporte', () => {
    const withMetros = fc.record({
      pvp_proveedor: fc.double({ min: 0, max: 100_000, noNaN: true, noDefaultInfinity: true }),
      desc_prov_1: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      coste_transporte: fc.double({ min: 0, max: 10_000, noNaN: true, noDefaultInfinity: true }),
      iva_porcentaje: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_camion_vip: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_camion: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_oferta: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_vip: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_empresas: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_empresas_a: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      metros_articulo: fc.double({ min: 0.01, max: 1000, noNaN: true, noDefaultInfinity: true }) as fc.Arbitrary<number | null>,
    });

    fc.assert(
      fc.property(withMetros, (input) => {
        const result = calculatePrices(input);
        expect(result.coste_neto_m2).toBe(result.coste_neto);
        const expectedTrans = round4(Math.max(result.coste_neto_m2! + input.coste_transporte, 0));
        expect(result.coste_m2_trans).toBeCloseTo(expectedTrans, 4);
      }),
      { numRuns: 200 },
    );
  });

  it('metros_articulo null → coste_neto_m2 = null, coste_m2_trans = null', () => {
    const withoutMetros = fc.record({
      pvp_proveedor: fc.double({ min: 0, max: 100_000, noNaN: true, noDefaultInfinity: true }),
      desc_prov_1: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      coste_transporte: fc.double({ min: 0, max: 10_000, noNaN: true, noDefaultInfinity: true }),
      iva_porcentaje: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_camion_vip: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_camion: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_oferta: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_vip: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_empresas: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      desc_empresas_a: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
      metros_articulo: fc.constant(null) as fc.Arbitrary<number | null>,
    });

    fc.assert(
      fc.property(withoutMetros, (input) => {
        const result = calculatePrices(input);
        expect(result.coste_neto_m2).toBeNull();
        expect(result.coste_m2_trans).toBeNull();
      }),
      { numRuns: 200 },
    );
  });

  it('all outputs are non-negative', () => {
    fc.assert(
      fc.property(priceInputsArb, (input) => {
        const result = calculatePrices(input);
        expect(result.coste_neto).toBeGreaterThanOrEqual(0);
        expect(result.pre_pvp).toBeGreaterThanOrEqual(0);
        expect(result.pvp).toBeGreaterThanOrEqual(0);
        expect(result.neto_camion_vip).toBeGreaterThanOrEqual(0);
        expect(result.neto_camion).toBeGreaterThanOrEqual(0);
        expect(result.neto_oferta).toBeGreaterThanOrEqual(0);
        expect(result.neto_vip).toBeGreaterThanOrEqual(0);
        expect(result.neto_empresas).toBeGreaterThanOrEqual(0);
        expect(result.neto_empresas_a).toBeGreaterThanOrEqual(0);
        if (result.coste_neto_m2 !== null) {
          expect(result.coste_neto_m2).toBeGreaterThanOrEqual(0);
        }
        if (result.coste_m2_trans !== null) {
          expect(result.coste_m2_trans).toBeGreaterThanOrEqual(0);
        }
      }),
      { numRuns: 200 },
    );
  });
});
