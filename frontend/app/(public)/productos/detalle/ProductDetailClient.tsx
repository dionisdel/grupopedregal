"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { Package, Plus, Warehouse } from "lucide-react";
import { productService } from "@/services/product.service";
import type { ProductDetail } from "@/services/types";
import { useCart } from "@/context/CartContext";

export default function ProductDetailClient() {
  const searchParams = useSearchParams();
  const slug = searchParams.get("slug");
  const { addItem } = useCart();

  const [product, setProduct] = useState<ProductDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [added, setAdded] = useState(false);

  useEffect(() => {
    if (!slug) { setLoading(false); return; }
    productService.fetchBySlug(slug)
      .then(setProduct)
      .catch(() => setProduct(null))
      .finally(() => setLoading(false));
  }, [slug]);

  const handleAdd = () => {
    if (!product) return;
    addItem({
      id: product.id,
      nombre: product.nombre,
      slug: product.slug,
      imagen_url: product.imagen_url,
      pvp: product.pvp,
      iva_porcentaje: product.iva_porcentaje,
    });
    setAdded(true);
    setTimeout(() => setAdded(false), 2000);
  };

  if (loading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="animate-pulse grid grid-cols-1 md:grid-cols-2 gap-8">
          <div className="aspect-square bg-gray-200 rounded-xl" />
          <div className="space-y-4">
            <div className="h-8 bg-gray-200 rounded w-3/4" />
            <div className="h-4 bg-gray-200 rounded w-1/2" />
            <div className="h-6 bg-gray-200 rounded w-1/3" />
            <div className="h-32 bg-gray-200 rounded" />
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
        <h1 className="text-xl font-bold text-[#333] mb-4">Producto no encontrado</h1>
        <p className="text-gray-500 mb-6">El producto que buscas no existe o ha sido retirado.</p>
        <Link href="/" className="text-[#E8751A] font-medium hover:underline">Volver al inicio</Link>
      </div>
    );
  }

  const imgSrc = product.imagen_url
    ? `${process.env.NEXT_PUBLIC_API_URL || ""}${product.imagen_url}`
    : null;

  const ivaAmount = product.pvp - product.pre_pvp;

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
        {/* Image */}
        <div className="aspect-square bg-[#F5F5F5] rounded-xl overflow-hidden flex items-center justify-center">
          {imgSrc ? (
            <img src={imgSrc} alt={product.nombre} className="w-full h-full object-contain p-4" />
          ) : (
            <Package size={80} className="text-gray-300" />
          )}
        </div>

        {/* Info */}
        <div className="flex flex-col gap-4">
          <div>
            <h1 className="text-2xl sm:text-3xl font-bold text-[#333]">{product.nombre}</h1>
            <div className="flex items-center gap-3 mt-2 text-sm text-gray-500">
              <span>Código: {product.codigo_articulo}</span>
              {product.marca && <span>· {product.marca}</span>}
            </div>
          </div>

          {/* Price breakdown */}
          <div className="bg-[#F5F5F5] rounded-xl p-4 space-y-2">
            <div className="flex justify-between text-sm text-gray-600">
              <span>Precio sin IVA</span>
              <span>{product.pre_pvp.toFixed(2)} €</span>
            </div>
            <div className="flex justify-between text-sm text-gray-600">
              <span>IVA ({product.iva_porcentaje}%)</span>
              <span>{ivaAmount.toFixed(2)} €</span>
            </div>
            <div className="border-t border-gray-300 pt-2 flex justify-between">
              <span className="font-bold text-[#333]">PVP (IVA incl.)</span>
              <span className="text-xl font-bold text-[#E8751A]">{product.pvp.toFixed(2)} €</span>
            </div>
          </div>

          {/* Add to cart */}
          <button
            onClick={handleAdd}
            className={`w-full flex items-center justify-center gap-2 py-3 rounded-lg text-sm font-bold text-white transition-colors ${
              added ? "bg-green-600" : "bg-[#E8751A] hover:opacity-90"
            }`}
          >
            <Plus size={18} />
            {added ? "¡Añadido al carrito!" : "Añadir al carrito"}
          </button>

          {/* Description */}
          {product.descripcion && (
            <div>
              <h2 className="text-sm font-semibold text-[#333] mb-1">Descripción</h2>
              <p className="text-sm text-gray-600 leading-relaxed">{product.descripcion}</p>
            </div>
          )}

          {/* Specs */}
          <div>
            <h2 className="text-sm font-semibold text-[#333] mb-2">Especificaciones</h2>
            <div className="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
              {product.kg_litro != null && (
                <><span className="text-gray-500">Peso</span><span className="text-[#333]">{product.kg_litro} kg</span></>
              )}
              {product.largo != null && (
                <><span className="text-gray-500">Largo</span><span className="text-[#333]">{product.largo} cm</span></>
              )}
              {product.ancho != null && (
                <><span className="text-gray-500">Ancho</span><span className="text-[#333]">{product.ancho} cm</span></>
              )}
              {product.metros_articulo != null && (
                <><span className="text-gray-500">m² por artículo</span><span className="text-[#333]">{product.metros_articulo}</span></>
              )}
              {product.unidades_por_articulo != null && (
                <><span className="text-gray-500">Uds. por artículo</span><span className="text-[#333]">{product.unidades_por_articulo}</span></>
              )}
              {product.articulos_por_embalaje != null && (
                <><span className="text-gray-500">Arts. por embalaje</span><span className="text-[#333]">{product.articulos_por_embalaje}</span></>
              )}
              {product.unidades_palet != null && (
                <><span className="text-gray-500">Uds. por palet</span><span className="text-[#333]">{product.unidades_palet}</span></>
              )}
              <span className="text-gray-500">Palet retornable</span>
              <span className="text-[#333]">{product.palet_retornable ? "Sí" : "No"}</span>
            </div>
          </div>

          {/* Stock per warehouse */}
          {product.stock_por_almacen && product.stock_por_almacen.length > 0 && (
            <div>
              <h2 className="text-sm font-semibold text-[#333] mb-2 flex items-center gap-1.5">
                <Warehouse size={14} />
                Disponibilidad por almacén
              </h2>
              <div className="space-y-1">
                {product.stock_por_almacen.map((s, i) => (
                  <div key={i} className="flex justify-between text-sm">
                    <span className="text-gray-600">{s.almacen}</span>
                    <span className={s.cantidad > 0 ? "text-green-600 font-medium" : "text-red-500"}>
                      {s.cantidad > 0 ? `${s.cantidad} uds.` : "Sin stock"}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
