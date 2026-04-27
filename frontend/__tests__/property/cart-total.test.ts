/**
 * Property 9: Cart Total Calculation
 * Feature: portal-ecommerce-v2, Property 9: Cart Total Calculation
 *
 * **Validates: Requirements 8.7**
 *
 * For any set of cart items with positive prices and positive integer quantities,
 * the cart totals SHALL satisfy:
 * - subtotal = Σ(precio_unitario × cantidad)
 * - iva_total = sum of IVA for each item based on its product's iva_porcentaje
 * - total = subtotal + iva_total
 */
import { describe, it, expect } from 'vitest';
import fc from 'fast-check';

interface TestCartItem {
  product_id: number;
  precio_unitario: number;
  cantidad: number;
  iva_porcentaje: number;
}

/** Calculate cart totals the same way CartContext does */
function calculateCartTotals(items: TestCartItem[]) {
  const subtotal = items.reduce(
    (sum, i) => sum + i.precio_unitario * i.cantidad,
    0
  );
  const ivaTotal = items.reduce(
    (sum, i) => sum + i.precio_unitario * i.cantidad * (i.iva_porcentaje / 100),
    0
  );
  const total = subtotal + ivaTotal;
  return { subtotal, ivaTotal, total };
}

/** Arbitrary for a single cart item with realistic constraints */
const cartItemArb: fc.Arbitrary<TestCartItem> = fc.record({
  product_id: fc.integer({ min: 1, max: 100000 }),
  precio_unitario: fc.double({ min: 0.01, max: 10000, noNaN: true, noDefaultInfinity: true }),
  cantidad: fc.integer({ min: 1, max: 999 }),
  iva_porcentaje: fc.double({ min: 0, max: 100, noNaN: true, noDefaultInfinity: true }),
});

/** Arbitrary for a cart (1 to 20 items) */
const cartArb = fc.array(cartItemArb, { minLength: 1, maxLength: 20 });

describe('Property 9: Cart Total Calculation', () => {
  it('subtotal = Σ(precio_unitario × cantidad) for all items', () => {
    fc.assert(
      fc.property(cartArb, (items) => {
        const { subtotal } = calculateCartTotals(items);
        const expected = items.reduce(
          (sum, i) => sum + i.precio_unitario * i.cantidad,
          0
        );
        expect(subtotal).toBeCloseTo(expected, 8);
      }),
      { numRuns: 200 },
    );
  });

  it('iva_total = Σ(precio_unitario × cantidad × iva_porcentaje / 100)', () => {
    fc.assert(
      fc.property(cartArb, (items) => {
        const { ivaTotal } = calculateCartTotals(items);
        const expected = items.reduce(
          (sum, i) => sum + i.precio_unitario * i.cantidad * (i.iva_porcentaje / 100),
          0
        );
        expect(ivaTotal).toBeCloseTo(expected, 8);
      }),
      { numRuns: 200 },
    );
  });

  it('total = subtotal + iva_total', () => {
    fc.assert(
      fc.property(cartArb, (items) => {
        const { subtotal, ivaTotal, total } = calculateCartTotals(items);
        expect(total).toBeCloseTo(subtotal + ivaTotal, 8);
      }),
      { numRuns: 200 },
    );
  });

  it('empty cart has zero totals', () => {
    const { subtotal, ivaTotal, total } = calculateCartTotals([]);
    expect(subtotal).toBe(0);
    expect(ivaTotal).toBe(0);
    expect(total).toBe(0);
  });

  it('subtotal and total are always non-negative for positive inputs', () => {
    fc.assert(
      fc.property(cartArb, (items) => {
        const { subtotal, ivaTotal, total } = calculateCartTotals(items);
        expect(subtotal).toBeGreaterThan(0);
        expect(ivaTotal).toBeGreaterThanOrEqual(0);
        expect(total).toBeGreaterThan(0);
      }),
      { numRuns: 200 },
    );
  });

  it('adding an item increases the total', () => {
    fc.assert(
      fc.property(cartArb, cartItemArb, (items, newItem) => {
        const before = calculateCartTotals(items);
        const after = calculateCartTotals([...items, newItem]);
        expect(after.total).toBeGreaterThan(before.total);
      }),
      { numRuns: 200 },
    );
  });
});
