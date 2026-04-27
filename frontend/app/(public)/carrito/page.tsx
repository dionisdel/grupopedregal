"use client";

import { useCart } from "@/context/CartContext";
import { useUser } from "@/context/UserContext";
import { Package, Trash2, Minus, Plus, ShoppingCart } from "lucide-react";
import Link from "next/link";

export default function CartPage() {
  const { items, removeItem, updateQuantity, clearCart, itemCount, subtotal, ivaTotal, total, loading } = useCart();
  const { user } = useUser();

  if (loading) {
    return (
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="animate-pulse space-y-4">
          <div className="h-8 bg-gray-200 rounded w-1/3" />
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-24 bg-gray-200 rounded" />
          ))}
        </div>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <ShoppingCart size={64} className="mx-auto text-gray-300 mb-4" />
        <h1 className="text-xl font-bold text-[#333] mb-2">Tu carrito está vacío</h1>
        <p className="text-gray-500 mb-6">Explora nuestro catálogo y añade productos.</p>
        <Link
          href="/categorias"
          className="inline-block px-6 py-3 rounded-lg text-sm font-bold text-white bg-[#E8751A] hover:opacity-90 transition-colors"
        >
          Ver productos
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-[#333]">
          Mi carrito ({itemCount} {itemCount === 1 ? "artículo" : "artículos"})
        </h1>
        <button
          onClick={clearCart}
          className="text-sm text-gray-400 hover:text-red-500 transition-colors"
        >
          Vaciar carrito
        </button>
      </div>

      {/* Cart items */}
      <div className="space-y-3 mb-8">
        {items.map((item) => {
          const imgSrc = item.imagen_url
            ? `${process.env.NEXT_PUBLIC_API_URL || ""}${item.imagen_url}`
            : null;
          const lineSubtotal = item.precio_unitario * item.cantidad;

          return (
            <div
              key={item.product_id}
              className="flex items-center gap-4 bg-white rounded-xl border border-gray-200 p-4"
            >
              {/* Image */}
              <Link
                href={`/productos/detalle?slug=${item.slug}`}
                className="shrink-0 w-20 h-20 bg-[#F5F5F5] rounded-lg overflow-hidden flex items-center justify-center"
              >
                {imgSrc ? (
                  <img src={imgSrc} alt={item.nombre} className="w-full h-full object-contain p-1" />
                ) : (
                  <Package size={28} className="text-gray-300" />
                )}
              </Link>

              {/* Info */}
              <div className="flex-1 min-w-0">
                <Link
                  href={`/productos/detalle?slug=${item.slug}`}
                  className="text-sm font-medium text-[#333] hover:text-[#E8751A] transition-colors line-clamp-2"
                >
                  {item.nombre}
                </Link>
                <p className="text-xs text-gray-400 mt-0.5">
                  {item.precio_unitario.toFixed(2)} €/ud.
                </p>
              </div>

              {/* Quantity selector */}
              <div className="flex items-center gap-1 shrink-0">
                <button
                  onClick={() => updateQuantity(item.product_id, item.cantidad - 1)}
                  disabled={item.cantidad <= 1}
                  className="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:border-[#E8751A] hover:text-[#E8751A] transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                  aria-label="Reducir cantidad"
                >
                  <Minus size={14} />
                </button>
                <span className="w-10 text-center text-sm font-medium text-[#333]">
                  {item.cantidad}
                </span>
                <button
                  onClick={() => updateQuantity(item.product_id, item.cantidad + 1)}
                  className="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:border-[#E8751A] hover:text-[#E8751A] transition-colors"
                  aria-label="Aumentar cantidad"
                >
                  <Plus size={14} />
                </button>
              </div>

              {/* Line subtotal */}
              <div className="text-right shrink-0 w-24">
                <p className="text-sm font-bold text-[#333]">{lineSubtotal.toFixed(2)} €</p>
              </div>

              {/* Remove */}
              <button
                onClick={() => removeItem(item.product_id)}
                className="shrink-0 text-gray-400 hover:text-red-500 transition-colors"
                aria-label="Eliminar producto"
              >
                <Trash2 size={18} />
              </button>
            </div>
          );
        })}
      </div>

      {/* Summary */}
      <div className="bg-[#F5F5F5] rounded-xl p-6 space-y-3">
        <div className="flex justify-between text-sm text-gray-600">
          <span>Subtotal (sin IVA)</span>
          <span>{subtotal.toFixed(2)} €</span>
        </div>
        <div className="flex justify-between text-sm text-gray-600">
          <span>IVA</span>
          <span>{ivaTotal.toFixed(2)} €</span>
        </div>
        <div className="border-t border-gray-300 pt-3 flex justify-between">
          <span className="text-lg font-bold text-[#333]">Total</span>
          <span className="text-lg font-bold text-[#E8751A]">{total.toFixed(2)} €</span>
        </div>

        {!user && (
          <p className="text-xs text-gray-400 text-center pt-2">
            <Link href="/login?redirect=/carrito" className="text-[#E8751A] hover:underline">
              Inicia sesión
            </Link>{" "}
            para guardar tu carrito y completar tu pedido.
          </p>
        )}
      </div>
    </div>
  );
}
