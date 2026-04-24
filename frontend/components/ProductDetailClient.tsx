"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { ArrowLeft, Package, Weight, Ruler, Layers, Box, Grid3X3 } from "lucide-react";
import {
  catalogService,
  type ProductDetail,
} from "@/services/catalog.service";
import MaterialCalculator from "@/components/MaterialCalculator";
import PdfActions from "@/components/PdfActions";

function formatPrice(value: number | null | undefined): string {
  if (value === null || value === undefined) return "—";
  return new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(value);
}

function SpecRow({ icon, label, value, unit }: { icon: React.ReactNode; label: string; value: number | null; unit: string }) {
  if (value === null || value === undefined) return null;
  return (
    <div className="flex items-center gap-3 py-3 border-b border-gray-100 last:border-b-0">
      <span className="text-[#E8751A]">{icon}</span>
      <span className="text-sm text-gray-500 min-w-[140px]">{label}</span>
      <span className="text-sm font-semibold text-[#333333]">{value} {unit}</span>
    </div>
  );
}

function DetailSkeleton() {
  return (
    <div className="min-h-[60vh] bg-[#F5F5F5]">
      <div className="bg-white border-b border-gray-200"><div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-5"><div className="h-5 w-32 bg-gray-200 rounded animate-pulse" /></div></div>
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div className="grid grid-cols-1 lg:grid-cols-3 gap-8"><div className="lg:col-span-2 space-y-6"><div className="h-8 w-3/4 bg-gray-200 rounded animate-pulse" /><div className="h-48 bg-gray-200 rounded-xl animate-pulse" /></div><div className="space-y-4"><div className="h-40 bg-gray-200 rounded-xl animate-pulse" /></div></div></div>
    </div>
  );
}

export default function ProductDetailClient() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const slug = searchParams.get("slug") ?? "";
  const [product, setProduct] = useState<ProductDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [calcM2, setCalcM2] = useState<number | null>(null);
  const [calcMerma, setCalcMerma] = useState<number>(5);

  useEffect(() => {
    if (!slug) return;
    setLoading(true); setError(null);
    catalogService.getProductBySlug(slug).then(setProduct).catch(() => setError("No se pudo cargar el producto.")).finally(() => setLoading(false));
  }, [slug]);

  if (loading) return <DetailSkeleton />;
  if (error || !product) return (
    <div className="min-h-[60vh] bg-[#F5F5F5] flex items-center justify-center">
      <div className="text-center space-y-4">
        <Package size={48} className="text-gray-300 mx-auto" />
        <p className="text-gray-500">{error ?? "Producto no encontrado."}</p>
        <button onClick={() => router.push("/productos")} className="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-[#E8751A]">Volver</button>
      </div>
    </div>
  );

  const { specs, codigos, precio } = product;
  return (
    <div className="min-h-[60vh] bg-[#F5F5F5]">
      <div className="bg-white border-b border-gray-200"><div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4"><button onClick={() => router.push("/productos")} className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#E8751A]"><ArrowLeft size={16} />Volver al explorador</button></div></div>
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <div><h1 className="text-2xl sm:text-3xl font-bold text-[#333333]">{product.nombre}</h1>
              <div className="flex flex-wrap items-center gap-3 mt-2">
                {product.sku && <span className="text-xs font-medium text-gray-400 bg-gray-100 px-2 py-1 rounded">SKU: {product.sku}</span>}
                {product.categoria && <span className="text-xs font-medium text-[#E8751A] bg-orange-50 px-2 py-1 rounded">{product.categoria}</span>}
                {product.marca && <span className="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">{product.marca}</span>}
              </div>
            </div>
            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
              {product.imagen_principal_url ? <img src={product.imagen_principal_url} alt={product.nombre} className="w-full h-64 sm:h-80 object-contain bg-white p-4" /> : <div className="w-full h-64 sm:h-80 flex items-center justify-center bg-gray-50"><Package size={64} className="text-gray-200" /></div>}
            </div>
            {product.descripcion && <div className="bg-white rounded-xl border border-gray-200 p-6"><h2 className="text-sm font-bold text-[#333333] uppercase tracking-wider mb-3">Descripción</h2><p className="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{product.descripcion}</p></div>}
            {specs && <div className="bg-white rounded-xl border border-gray-200 p-6"><h2 className="text-sm font-bold text-[#333333] uppercase tracking-wider mb-3">Especificaciones técnicas</h2><div><SpecRow icon={<Weight size={16}/>} label="Peso" value={specs.peso_kg} unit="kg"/><SpecRow icon={<Ruler size={16}/>} label="Largo" value={specs.largo_cm} unit="cm"/><SpecRow icon={<Ruler size={16}/>} label="Ancho" value={specs.ancho_cm} unit="cm"/><SpecRow icon={<Grid3X3 size={16}/>} label="m² por unidad" value={specs.m2_por_unidad} unit="m²"/><SpecRow icon={<Box size={16}/>} label="Uds. por embalaje" value={specs.unidades_por_embalaje} unit="uds"/><SpecRow icon={<Layers size={16}/>} label="Uds. por palet" value={specs.unidades_por_palet} unit="uds"/></div></div>}
            {codigos.length > 0 && <div className="bg-white rounded-xl border border-gray-200 p-6"><h2 className="text-sm font-bold text-[#333333] uppercase tracking-wider mb-3">Códigos alternativos</h2><div className="space-y-2">{codigos.map((c,i)=><div key={i} className="flex items-center gap-3 text-sm"><span className="text-gray-400 font-medium min-w-[80px] uppercase">{c.tipo}</span><span className="text-[#333333] font-mono">{c.codigo}</span></div>)}</div></div>}
            {specs && specs.m2_por_unidad != null && specs.m2_por_unidad > 0 && <MaterialCalculator productId={product.id} productName={product.nombre} onStateChange={(m2,merma)=>{setCalcM2(m2);setCalcMerma(merma);}}/>}
          </div>
          <div className="space-y-5"><div className="bg-white rounded-xl border border-gray-200 p-6 sticky top-6"><h2 className="text-sm font-bold text-[#333333] uppercase tracking-wider mb-4">Precio PVP</h2><div className="space-y-3"><div className="flex justify-between items-center text-sm"><span className="text-gray-500">Base</span><span className="font-semibold text-[#333333]">{formatPrice(precio.base)}</span></div><div className="flex justify-between items-center text-sm"><span className="text-gray-500">IVA (21%)</span><span className="font-semibold text-[#333333]">{formatPrice(precio.iva)}</span></div><div className="border-t border-gray-200 pt-3 flex justify-between items-center"><span className="text-sm font-bold text-[#333333]">Total</span><span className="text-xl font-bold text-[#E8751A]">{formatPrice(precio.total)}</span></div></div><div className="mt-6"><PdfActions productId={product.id} m2={calcM2 ?? undefined} merma={calcMerma}/></div></div></div>
        </div>
      </div>
    </div>
  );
}
