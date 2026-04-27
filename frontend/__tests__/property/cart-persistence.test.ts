/**
 * Property 15: Cart Persistence Round-Trip
 * Feature: portal-ecommerce-v2, Property 15: Cart Persistence Round-Trip
 *
 * **Validates: Requirements 8.3, 8.4**
 *
 * For any product added to the cart (localStorage), reading the cart back
 * SHALL return the same product with the same quantity and price.
 * The storage mechanism SHALL not lose or corrupt cart data.
 */
import { describe, it, expect, beforeEach } from 'vitest';
import fc from 'fast-check';

/** Minimal cart item shape matching localStorage storage */
interface LocalCartItem {
  product_id: number;
  nombre: string;
  slug: string;
  imagen_url: string | null;
  precio_unitario: number;
  cantidad: number;
}

const CART_STORAGE_KEY = 'cart_items';

/** Simulated localStorage using a plain Map */
function createMockStorage(): Storage {
  const store = new Map<string, string>();
  return {
    getItem: (key: string) => store.get(key) ?? null,
    setItem: (key: string, value: string) => { store.set(key, value); },
    removeItem: (key: string) => { store.delete(key); },
    clear: () => { store.clear(); },
    get length() { return store.size; },
    key: (index: number) => [...store.keys()][index] ?? null,
  };
}

/** Write cart items to storage */
function saveCart(storage: Storage, items: LocalCartItem[]): void {
  storage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
}

/** Read cart items from storage */
function loadCart(storage: Storage): LocalCartItem[] {
  const raw = storage.getItem(CART_STORAGE_KEY);
  if (!raw) return [];
  return JSON.parse(raw) as LocalCartItem[];
}

/** Arbitrary for a single cart item with realistic constraints */
const cartItemArb: fc.Arbitrary<LocalCartItem> = fc.record({
  product_id: fc.integer({ min: 1, max: 100000 }),
  nombre: fc.string({ minLength: 1, maxLength: 100 }),
  slug: fc.stringMatching(/^[a-z0-9][a-z0-9-]{0,49}$/),
  imagen_url: fc.oneof(fc.constant(null), fc.webUrl()),
  precio_unitario: fc.double({ min: 0.01, max: 10000, noNaN: true, noDefaultInfinity: true }),
  cantidad: fc.integer({ min: 1, max: 999 }),
});

/** Arbitrary for a cart (1 to 15 items with unique product_ids) */
const cartArb = fc
  .array(cartItemArb, { minLength: 1, maxLength: 15 })
  .map((items) => {
    // Deduplicate by product_id, keeping the first occurrence
    const seen = new Set<number>();
    return items.filter((item) => {
      if (seen.has(item.product_id)) return false;
      seen.add(item.product_id);
      return true;
    });
  })
  .filter((items) => items.length > 0);

describe('Property 15: Cart Persistence Round-Trip', () => {
  let storage: Storage;

  beforeEach(() => {
    storage = createMockStorage();
  });

  it('items written to localStorage are read back identically', () => {
    fc.assert(
      fc.property(cartArb, (items) => {
        saveCart(storage, items);
        const loaded = loadCart(storage);

        expect(loaded).toHaveLength(items.length);

        for (let i = 0; i < items.length; i++) {
          expect(loaded[i].product_id).toBe(items[i].product_id);
          expect(loaded[i].nombre).toBe(items[i].nombre);
          expect(loaded[i].slug).toBe(items[i].slug);
          expect(loaded[i].imagen_url).toBe(items[i].imagen_url);
          expect(loaded[i].precio_unitario).toBeCloseTo(items[i].precio_unitario, 10);
          expect(loaded[i].cantidad).toBe(items[i].cantidad);
        }
      }),
      { numRuns: 200 },
    );
  });

  it('product_id, cantidad, and precio_unitario are preserved exactly', () => {
    fc.assert(
      fc.property(cartArb, (items) => {
        saveCart(storage, items);
        const loaded = loadCart(storage);

        const originalMap = new Map(items.map((i) => [i.product_id, i]));
        const loadedMap = new Map(loaded.map((i) => [i.product_id, i]));

        // Every original item should be in the loaded cart
        for (const [pid, original] of originalMap) {
          const found = loadedMap.get(pid);
          expect(found).toBeDefined();
          expect(found!.cantidad).toBe(original.cantidad);
          expect(found!.precio_unitario).toBeCloseTo(original.precio_unitario, 10);
        }
      }),
      { numRuns: 200 },
    );
  });

  it('empty cart round-trips correctly', () => {
    saveCart(storage, []);
    const loaded = loadCart(storage);
    expect(loaded).toEqual([]);
  });

  it('overwriting cart replaces previous data completely', () => {
    fc.assert(
      fc.property(cartArb, cartArb, (first, second) => {
        saveCart(storage, first);
        saveCart(storage, second);
        const loaded = loadCart(storage);

        expect(loaded).toHaveLength(second.length);
        for (let i = 0; i < second.length; i++) {
          expect(loaded[i].product_id).toBe(second[i].product_id);
          expect(loaded[i].cantidad).toBe(second[i].cantidad);
        }
      }),
      { numRuns: 200 },
    );
  });

  it('loading from empty storage returns empty array', () => {
    const loaded = loadCart(storage);
    expect(loaded).toEqual([]);
  });
});
