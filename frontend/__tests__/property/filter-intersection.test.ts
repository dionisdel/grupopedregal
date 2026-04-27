/**
 * Property 6: Dynamic Filter AND Intersection
 * Feature: portal-ecommerce-v2, Property 6: Dynamic Filter AND Intersection
 *
 * Validates: Requirements 5.2, 5.3, 5.5
 *
 * For any set of products with filtros_dinamicos and any combination of
 * filter key-value pairs, the filtered result SHALL contain only products
 * that match ALL selected filter criteria simultaneously (AND intersection).
 * The set of available filter keys SHALL be the union of all keys present
 * in filtros_dinamicos across all products.
 */
import { describe, it, expect } from 'vitest';
import fc from 'fast-check';
import type { ProductListItem } from '../../services/types';

// ---- Pure functions extracted from the frontend filtering logic ----

/**
 * Extracts available filters from a list of products.
 * Returns a map of filter key → unique values across all products.
 * This mirrors the backend /api/categories/{id}/filters endpoint logic.
 */
function extractAvailableFilters(
  products: Pick<ProductListItem, 'filtros_dinamicos'>[],
): Record<string, string[]> {
  const filterMap: Record<string, Set<string>> = {};
  for (const p of products) {
    if (!p.filtros_dinamicos) continue;
    for (const [key, value] of Object.entries(p.filtros_dinamicos)) {
      if (!filterMap[key]) filterMap[key] = new Set();
      filterMap[key].add(value);
    }
  }
  const result: Record<string, string[]> = {};
  for (const [key, values] of Object.entries(filterMap)) {
    result[key] = Array.from(values).sort();
  }
  return result;
}

/**
 * Applies AND intersection filtering — same logic as CategoryPageClient.tsx:
 *   products.filter(p => Object.entries(selectedFilters).every(
 *     ([key, value]) => p.filtros_dinamicos && p.filtros_dinamicos[key] === value
 *   ))
 */
function applyFilters(
  products: Pick<ProductListItem, 'filtros_dinamicos'>[],
  selectedFilters: Record<string, string>,
): Pick<ProductListItem, 'filtros_dinamicos'>[] {
  return products.filter((p) =>
    Object.entries(selectedFilters).every(
      ([key, value]) => p.filtros_dinamicos && p.filtros_dinamicos[key] === value,
    ),
  );
}

// ---- Generators ----

const filterKeyArb = fc.constantFrom('color', 'formato', 'acabado', 'material', 'tipo');

const filterValueArb = fc.constantFrom('blanco', 'negro', 'rojo', '25kg', '50kg', 'mate', 'brillo');

const filtrosDinamicosArb: fc.Arbitrary<Record<string, string>> = fc.dictionary(
  filterKeyArb,
  filterValueArb,
  { minKeys: 0, maxKeys: 5 },
);

const productWithFiltersArb = filtrosDinamicosArb.map((fd) => ({
  filtros_dinamicos: fd,
}));

const productListArb = fc.array(productWithFiltersArb, { minLength: 1, maxLength: 30 });

describe('Property 6: Dynamic Filter AND Intersection', () => {
  it('filtered result contains only products matching ALL selected filters', () => {
    fc.assert(
      fc.property(
        productListArb,
        fc.dictionary(filterKeyArb, filterValueArb, { minKeys: 1, maxKeys: 3 }),
        (products, selectedFilters) => {
          const filtered = applyFilters(products, selectedFilters);

          // Every product in the result must match ALL selected filters
          for (const p of filtered) {
            for (const [key, value] of Object.entries(selectedFilters)) {
              expect(p.filtros_dinamicos[key]).toBe(value);
            }
          }

          // Every product NOT in the result must fail at least one filter
          const filteredSet = new Set(filtered);
          for (const p of products) {
            if (!filteredSet.has(p)) {
              const matchesAll = Object.entries(selectedFilters).every(
                ([key, value]) =>
                  p.filtros_dinamicos && p.filtros_dinamicos[key] === value,
              );
              expect(matchesAll).toBe(false);
            }
          }
        },
      ),
      { numRuns: 200 },
    );
  });

  it('available filter keys are the union of all keys across products', () => {
    fc.assert(
      fc.property(productListArb, (products) => {
        const available = extractAvailableFilters(products);
        const availableKeys = new Set(Object.keys(available));

        // Collect all keys from all products
        const allKeys = new Set<string>();
        for (const p of products) {
          if (p.filtros_dinamicos) {
            for (const key of Object.keys(p.filtros_dinamicos)) {
              allKeys.add(key);
            }
          }
        }

        // Available keys must equal the union of all product keys
        expect(availableKeys).toEqual(allKeys);
      }),
      { numRuns: 200 },
    );
  });

  it('available filter values for each key are the union of values across products', () => {
    fc.assert(
      fc.property(productListArb, (products) => {
        const available = extractAvailableFilters(products);

        for (const [key, values] of Object.entries(available)) {
          const expectedValues = new Set<string>();
          for (const p of products) {
            if (p.filtros_dinamicos && p.filtros_dinamicos[key] !== undefined) {
              expectedValues.add(p.filtros_dinamicos[key]);
            }
          }
          expect(new Set(values)).toEqual(expectedValues);
        }
      }),
      { numRuns: 200 },
    );
  });

  it('empty filter selection returns all products', () => {
    fc.assert(
      fc.property(productListArb, (products) => {
        const filtered = applyFilters(products, {});
        expect(filtered.length).toBe(products.length);
      }),
      { numRuns: 200 },
    );
  });

  it('adding a filter on a new key never increases the result set (monotonicity)', () => {
    // Use two distinct keys to ensure we're truly adding a constraint
    const distinctKeyPairArb = fc.tuple(filterKeyArb, filterKeyArb).filter(
      ([k1, k2]) => k1 !== k2,
    );

    fc.assert(
      fc.property(
        productListArb,
        distinctKeyPairArb,
        filterValueArb,
        filterValueArb,
        (products, [key1, key2], val1, val2) => {
          const filter1 = { [key1]: val1 };
          const filter2 = { [key1]: val1, [key2]: val2 };

          const result1 = applyFilters(products, filter1);
          const result2 = applyFilters(products, filter2);

          // Adding a filter on a different key → same or fewer results
          expect(result2.length).toBeLessThanOrEqual(result1.length);
        },
      ),
      { numRuns: 200 },
    );
  });
});
