"use client";

import { Package, Plus } from "lucide-react";
import Link from "next/link";
import type { ProductListItem } from "@/services/types";

interface ProductCardProps {
  product: ProductListItem;
  onAdd?: (product: ProductListItem) => void;
}

export default function ProductCard({ product, onAdd }: ProductCardProps) {
  const imgSrc = product.imagen_url
    ? `${process.env.NEXT_PUBLIC_API_URL || ""}${product.imagen_url}`
    : null;

  const handleAdd = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    onAdd?.(product);
  };

  return (
    <Link
      href={`/productos/detalle?slug=${product.slug}`}
      className="group flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md hover:border-[#E8751A]/40 transition-all"
    >
      {/* Image */}
      <div className="relative w-full aspect-square bg-[#F5F5F5] overflow-hidden">
        {imgSrc ? (
          <img
            src={imgSrc}
            alt={product.nombre}
            className="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <Package size={48} className="text-gray-300" />
          </div>
        )}
      </div>

      {/* Content */}
      <div className="p-3 flex flex-col gap-1.5 flex-1">
        <h3 className="text-[#333] text-sm font-medium line-clamp-2 leading-snug min-h-[2.5rem]">
          {product.nombre}
        </h3>

        {product.marca && (
          <span className="text-xs text-gray-400">{product.marca}</span>
        )}

        {/* Stock */}
        <span className={`text-xs font-medium ${product.stock_total > 0 ? "text-green-600" : "text-red-500"}`}>
          {product.stock_total > 0 ? "En stock" : "Sin stock"}
        </span>

        {/* Price */}
        <div className="mt-auto pt-2">
          <p className="text-lg font-bold text-[#333]">
            {product.pvp.toFixed(2)} €
            {product.unidad && <span className="text-xs font-normal text-gray-400">/{product.unidad}</span>}
          </p>
          <p className="text-xs text-gray-400">
            {product.pre_pvp.toFixed(2)} € sin IVA
          </p>
        </div>

        {/* Add button */}
        {onAdd && (
          <button
            onClick={handleAdd}
            className="mt-2 w-full flex items-center justify-center gap-1.5 py-2 rounded-lg text-sm font-semibold text-white bg-[#E8751A] hover:opacity-90 transition-colors"
          >
            <Plus size={16} />
            Añadir
          </button>
        )}
      </div>
    </Link>
  );
}
