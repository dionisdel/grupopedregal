"use client";

import { createContext, useContext, useState, useEffect, useCallback } from "react";
import { useUser } from "@/context/UserContext";
import { cartService } from "@/services/cart.service";
import type { CartItem } from "@/services/types";

interface CartContextType {
  items: CartItem[];
  addItem: (product: { id: number; nombre: string; slug: string; imagen_url: string | null; pvp: number; iva_porcentaje?: number }) => void;
  removeItem: (productId: number) => void;
  updateQuantity: (productId: number, cantidad: number) => void;
  clearCart: () => void;
  itemCount: number;
  subtotal: number;
  ivaTotal: number;
  total: number;
  mergeCartOnLogin: () => Promise<void>;
  loading: boolean;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

const CART_STORAGE_KEY = "cart";

function getLocalCart(): CartItem[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = localStorage.getItem(CART_STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

function setLocalCart(items: CartItem[]) {
  if (typeof window === "undefined") return;
  try {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
    window.dispatchEvent(new Event("cart-updated"));
  } catch { /* ignore */ }
}

function clearLocalCart() {
  if (typeof window === "undefined") return;
  try {
    localStorage.removeItem(CART_STORAGE_KEY);
    window.dispatchEvent(new Event("cart-updated"));
  } catch { /* ignore */ }
}

export function CartProvider({ children }: { children: React.ReactNode }) {
  const { user, loading: userLoading } = useUser();
  const [items, setItems] = useState<CartItem[]>([]);
  const [loading, setLoading] = useState(false);

  // Load cart: localStorage for public, DB for authenticated
  useEffect(() => {
    if (userLoading) return;

    if (user) {
      setLoading(true);
      cartService.getCart()
        .then((res) => {
          setItems(res.items.map((i) => ({
            ...i,
            subtotal: i.precio_unitario * i.cantidad,
          })));
        })
        .catch(() => setItems([]))
        .finally(() => setLoading(false));
    } else {
      setItems(getLocalCart());
    }
  }, [user, userLoading]);

  // Sync localStorage changes from other tabs (public users only)
  useEffect(() => {
    if (user) return;
    const handler = () => setItems(getLocalCart());
    window.addEventListener("storage", handler);
    window.addEventListener("cart-updated", handler);
    return () => {
      window.removeEventListener("storage", handler);
      window.removeEventListener("cart-updated", handler);
    };
  }, [user]);

  const addItem = useCallback(
    (product: { id: number; nombre: string; slug: string; imagen_url: string | null; pvp: number; iva_porcentaje?: number }) => {
      if (user) {
        // Authenticated: POST to API
        cartService.addItem(product.id, 1)
          .then((res) => {
            // res is the full cart response after our service change
            // Reload cart from API to stay in sync
            cartService.getCart().then((cart) => {
              setItems(cart.items.map((i) => ({
                ...i,
                subtotal: i.precio_unitario * i.cantidad,
              })));
            });
          })
          .catch(() => { /* ignore */ });
      } else {
        // Public: localStorage
        setItems((prev) => {
          const existing = prev.find((i) => i.product_id === product.id);
          let next: CartItem[];
          if (existing) {
            next = prev.map((i) =>
              i.product_id === product.id
                ? { ...i, cantidad: i.cantidad + 1, subtotal: i.precio_unitario * (i.cantidad + 1) }
                : i
            );
          } else {
            next = [
              ...prev,
              {
                product_id: product.id,
                nombre: product.nombre,
                slug: product.slug,
                imagen_url: product.imagen_url,
                precio_unitario: product.pvp,
                cantidad: 1,
                subtotal: product.pvp,
                iva_porcentaje: product.iva_porcentaje,
              } as CartItem,
            ];
          }
          setLocalCart(next);
          return next;
        });
      }
    },
    [user]
  );

  const removeItem = useCallback(
    (productId: number) => {
      if (user) {
        const item = items.find((i) => i.product_id === productId);
        if (item?.id) {
          cartService.removeItem(item.id)
            .then(() => {
              setItems((prev) => prev.filter((i) => i.product_id !== productId));
            })
            .catch(() => { /* ignore */ });
        }
      } else {
        setItems((prev) => {
          const next = prev.filter((i) => i.product_id !== productId);
          setLocalCart(next);
          return next;
        });
      }
    },
    [user, items]
  );

  const updateQuantity = useCallback(
    (productId: number, cantidad: number) => {
      if (cantidad < 1) return;
      if (user) {
        const item = items.find((i) => i.product_id === productId);
        if (item?.id) {
          cartService.updateItem(item.id, cantidad)
            .then(() => {
              setItems((prev) =>
                prev.map((i) =>
                  i.product_id === productId
                    ? { ...i, cantidad, subtotal: i.precio_unitario * cantidad }
                    : i
                )
              );
            })
            .catch(() => { /* ignore */ });
        }
      } else {
        setItems((prev) => {
          const next = prev.map((i) =>
            i.product_id === productId
              ? { ...i, cantidad, subtotal: i.precio_unitario * cantidad }
              : i
          );
          setLocalCart(next);
          return next;
        });
      }
    },
    [user, items]
  );

  const clearCart = useCallback(() => {
    setItems([]);
    if (!user) {
      clearLocalCart();
    }
  }, [user]);

  const mergeCartOnLogin = useCallback(async () => {
    const localItems = getLocalCart();
    if (localItems.length === 0) {
      // Just load DB cart
      try {
        const cart = await cartService.getCart();
        setItems(cart.items.map((i) => ({ ...i, subtotal: i.precio_unitario * i.cantidad })));
      } catch { /* ignore */ }
      return;
    }

    try {
      const mergePayload = localItems.map((i) => ({
        product_id: i.product_id,
        cantidad: i.cantidad,
      }));
      await cartService.mergeCart(mergePayload);
      clearLocalCart();
      // Reload merged cart from DB
      const cart = await cartService.getCart();
      setItems(cart.items.map((i) => ({ ...i, subtotal: i.precio_unitario * i.cantidad })));
    } catch {
      // If merge fails, still try to load DB cart
      try {
        const cart = await cartService.getCart();
        setItems(cart.items.map((i) => ({ ...i, subtotal: i.precio_unitario * i.cantidad })));
      } catch { /* ignore */ }
    }
  }, []);

  const itemCount = items.reduce((sum, i) => sum + i.cantidad, 0);
  const subtotal = items.reduce((sum, i) => sum + i.precio_unitario * i.cantidad, 0);
  // IVA: use iva_porcentaje from item if available, default 21%
  const ivaTotal = items.reduce((sum, i) => {
    const iva = (i as CartItem & { iva_porcentaje?: number }).iva_porcentaje ?? 21;
    return sum + i.precio_unitario * i.cantidad * (iva / 100);
  }, 0);
  const total = subtotal + ivaTotal;

  return (
    <CartContext.Provider
      value={{ items, addItem, removeItem, updateQuantity, clearCart, itemCount, subtotal, ivaTotal, total, mergeCartOnLogin, loading }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error("useCart must be used within CartProvider");
  return ctx;
}
