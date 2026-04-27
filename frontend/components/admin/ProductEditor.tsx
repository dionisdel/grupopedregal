"use client";

import { useState, useEffect, useCallback } from "react";
import { Save, X, Upload, Eye, EyeOff, Plus, Trash2 } from "lucide-react";
import { adminService } from "@/services/admin.service";
import { calculatePrices } from "@/services/price-calculator";
import type { PriceInputs } from "@/services/types";

interface ProductData {
  id?: number;
  codigo_articulo: string;
  descripcion: string;
  slug: string;
  categoria_id: number | null;
  proveedor_id: number | null;
  marca_id: number | null;
  codigo_proveedor: string;
  codigo_articulo_proveedor: string;
  kg_litro: number | null;
  largo: number | null;
  ancho: number | null;
  metros_articulo: number | null;
  unidades_por_articulo: number | null;
  articulos_por_embalaje: number | null;
  unidades_palet: number | null;
  palet_retornable: boolean;
  pvp_proveedor: number;
  desc_prov_1: number;
  coste_transporte: number;
  iva_porcentaje: number;
  desc_camion_vip: number;
  desc_camion: number;
  desc_oferta: number;
  desc_vip: number;
  desc_empresas: number;
  desc_empresas_a: number;
  filtros_dinamicos: Record<string, string>;
  imagen_url: string | null;
  estado_publicado: boolean;
  // calculated (read-only)
  coste_neto?: number;
  coste_neto_m2?: number | null;
  coste_m2_trans?: number | null;
  pre_pvp?: number;
  pvp?: number;
  neto_camion_vip?: number;
  neto_camion?: number;
  neto_oferta?: number;
  neto_vip?: number;
  neto_empresas?: number;
  neto_empresas_a?: number;
}

const defaultProduct: ProductData = {
  codigo_articulo: "",
  descripcion: "",
  slug: "",
  categoria_id: null,
  proveedor_id: null,
  marca_id: null,
  codigo_proveedor: "",
  codigo_articulo_proveedor: "",
  kg_litro: null,
  largo: null,
  ancho: null,
  metros_articulo: null,
  unidades_por_articulo: null,
  articulos_por_embalaje: null,
  unidades_palet: null,
  palet_retornable: false,
  pvp_proveedor: 0,
  desc_prov_1: 0,
  coste_transporte: 0,
  iva_porcentaje: 21,
  desc_camion_vip: 0,
  desc_camion: 0,
  desc_oferta: 0,
  desc_vip: 0,
  desc_empresas: 0,
  desc_empresas_a: 0,
  filtros_dinamicos: {},
  imagen_url: null,
  estado_publicado: false,
};

interface ProductEditorProps {
  productId?: number | null;
  categoryId?: number | null;
  onClose: () => void;
  onSaved?: () => void;
}

export default function ProductEditor({
  productId,
  categoryId,
  onClose,
  onSaved,
}: ProductEditorProps) {
  const [product, setProduct] = useState<ProductData>({
    ...defaultProduct,
    categoria_id: categoryId ?? null,
  });
  const [calculated, setCalculated] = useState(calculatePrices({ ...defaultProduct, metros_articulo: null }));
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [newFilterKey, setNewFilterKey] = useState("");
  const [newFilterValue, setNewFilterValue] = useState("");

  const recalculate = useCallback((p: ProductData) => {
    const input: PriceInputs = {
      pvp_proveedor: p.pvp_proveedor,
      desc_prov_1: p.desc_prov_1,
      coste_transporte: p.coste_transporte,
      iva_porcentaje: p.iva_porcentaje,
      desc_camion_vip: p.desc_camion_vip,
      desc_camion: p.desc_camion,
      desc_oferta: p.desc_oferta,
      desc_vip: p.desc_vip,
      desc_empresas: p.desc_empresas,
      desc_empresas_a: p.desc_empresas_a,
      metros_articulo: p.metros_articulo,
    };
    setCalculated(calculatePrices(input));
  }, []);

  useEffect(() => {
    if (!productId) return;
    setLoading(true);
    adminService
      .getProducts({ id: productId })
      .then((res) => {
        const p = res.data?.[0];
        if (p) {
          const loaded = { ...defaultProduct, ...p } as ProductData;
          setProduct(loaded);
          recalculate(loaded);
        }
      })
      .catch(() => setError("Error al cargar producto"))
      .finally(() => setLoading(false));
  }, [productId, recalculate]);

  const updateField = (field: keyof ProductData, value: unknown) => {
    setProduct((prev) => {
      const next = { ...prev, [field]: value };
      // Recalculate on price field changes
      const priceFields = [
        "pvp_proveedor", "desc_prov_1", "coste_transporte", "iva_porcentaje",
        "desc_camion_vip", "desc_camion", "desc_oferta", "desc_vip",
        "desc_empresas", "desc_empresas_a", "metros_articulo",
      ];
      if (priceFields.includes(field)) recalculate(next);
      return next;
    });
  };

  const generateSlug = (name: string) =>
    name
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "");

  const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      const { url } = await adminService.uploadImage(file, "product");
      setProduct((prev) => ({ ...prev, imagen_url: url }));
    } catch {
      setError("Error al subir imagen");
    }
  };

  const addFilter = () => {
    if (!newFilterKey.trim()) return;
    setProduct((prev) => ({
      ...prev,
      filtros_dinamicos: { ...prev.filtros_dinamicos, [newFilterKey.trim()]: newFilterValue.trim() },
    }));
    setNewFilterKey("");
    setNewFilterValue("");
  };

  const removeFilter = (key: string) => {
    setProduct((prev) => {
      const next = { ...prev.filtros_dinamicos };
      delete next[key];
      return { ...prev, filtros_dinamicos: next };
    });
  };

  const handleSave = async () => {
    if (!product.codigo_articulo.trim() || !product.descripcion.trim()) {
      setError("Código y descripción son obligatorios");
      return;
    }
    setSaving(true);
    setError(null);
    try {
      const payload = {
        ...product,
        slug: product.slug || generateSlug(product.descripcion),
      };
      if (product.id) {
        await adminService.updateProduct(product.id, payload as Record<string, unknown>);
      } else {
        await adminService.createProduct(payload as Record<string, unknown>);
      }
      onSaved?.();
      onClose();
    } catch (err: unknown) {
      const msg =
        err && typeof err === "object" && "response" in err
          ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
          : undefined;
      setError(msg || "Error al guardar producto");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20 text-sm text-gray-400">
        Cargando producto...
      </div>
    );
  }

  const numField = (label: string, field: keyof ProductData, opts?: { step?: string; min?: number }) => (
    <div>
      <label className="block text-xs font-medium text-gray-600 mb-1">{label}</label>
      <input
        type="number"
        step={opts?.step || "any"}
        min={opts?.min}
        value={product[field] as number ?? ""}
        onChange={(e) => updateField(field, e.target.value === "" ? null : Number(e.target.value))}
        className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
      />
    </div>
  );

  const calcField = (label: string, value: number | null | undefined) => (
    <div>
      <label className="block text-xs font-medium text-gray-400 mb-1">{label}</label>
      <div className="w-full bg-gray-100 border border-gray-200 rounded px-2 py-1.5 text-sm text-gray-600">
        {value != null ? value.toFixed(4) : "—"}
      </div>
    </div>
  );

  return (
    <div className="flex flex-col h-full">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b bg-gray-50 shrink-0">
        <h2 className="text-sm font-semibold text-[#333]">
          {product.id ? `Editar: ${product.descripcion}` : "Nuevo producto"}
        </h2>
        <div className="flex items-center gap-2">
          <button
            onClick={() => updateField("estado_publicado", !product.estado_publicado)}
            className={`cursor-pointer flex items-center gap-1 text-xs px-3 py-1.5 rounded font-medium transition-colors ${
              product.estado_publicado
                ? "bg-green-100 text-green-700"
                : "bg-gray-100 text-gray-500"
            }`}
            title={product.estado_publicado ? "Publicado" : "No publicado"}
          >
            {product.estado_publicado ? <Eye size={14} /> : <EyeOff size={14} />}
            {product.estado_publicado ? "Publicado" : "Borrador"}
          </button>
          <button
            onClick={handleSave}
            disabled={saving}
            className="cursor-pointer flex items-center gap-1 text-xs font-medium text-white bg-[#E8751A] px-3 py-1.5 rounded hover:opacity-90 disabled:opacity-50"
          >
            <Save size={14} />
            {saving ? "Guardando..." : "Guardar"}
          </button>
          <button onClick={onClose} className="cursor-pointer p-1.5 hover:bg-gray-200 rounded">
            <X size={16} />
          </button>
        </div>
      </div>

      {error && (
        <div className="mx-4 mt-2 text-xs text-red-600 bg-red-50 px-3 py-2 rounded">{error}</div>
      )}

      {/* Form */}
      <div className="flex-1 overflow-auto p-4 space-y-6">
        {/* Basic info */}
        <section>
          <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Información básica
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Código artículo *</label>
              <input
                type="text"
                value={product.codigo_articulo}
                onChange={(e) => updateField("codigo_articulo", e.target.value)}
                className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
              />
            </div>
            <div className="md:col-span-2">
              <label className="block text-xs font-medium text-gray-600 mb-1">Descripción *</label>
              <input
                type="text"
                value={product.descripcion}
                onChange={(e) => {
                  const desc = e.target.value;
                  setProduct((prev) => ({
                    ...prev,
                    descripcion: desc,
                    slug: prev.id ? prev.slug : generateSlug(desc),
                  }));
                }}
                className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Slug</label>
              <input
                type="text"
                value={product.slug}
                onChange={(e) => updateField("slug", e.target.value)}
                className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Cód. proveedor</label>
              <input
                type="text"
                value={product.codigo_proveedor}
                onChange={(e) => updateField("codigo_proveedor", e.target.value)}
                className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Cód. artículo proveedor</label>
              <input
                type="text"
                value={product.codigo_articulo_proveedor}
                onChange={(e) => updateField("codigo_articulo_proveedor", e.target.value)}
                className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
              />
            </div>
          </div>
        </section>

        {/* Image */}
        <section>
          <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Imagen</h3>
          <div className="flex items-center gap-4">
            {product.imagen_url && (
              <img
                src={`${process.env.NEXT_PUBLIC_API_URL || ""}${product.imagen_url}`}
                alt="Producto"
                className="w-20 h-20 object-contain border rounded"
              />
            )}
            <label className="flex items-center gap-1 text-xs text-[#E8751A] cursor-pointer hover:underline">
              <Upload size={14} />
              {product.imagen_url ? "Cambiar imagen" : "Subir imagen"}
              <input type="file" accept="image/*" className="hidden" onChange={handleImageUpload} />
            </label>
          </div>
        </section>

        {/* Physical specs */}
        <section>
          <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Especificaciones físicas
          </h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {numField("Kg/Litro", "kg_litro")}
            {numField("Largo (cm)", "largo")}
            {numField("Ancho (cm)", "ancho")}
            {numField("m² por artículo", "metros_articulo")}
            {numField("Uds. por artículo", "unidades_por_articulo", { step: "1", min: 0 })}
            {numField("Arts. por embalaje", "articulos_por_embalaje", { step: "1", min: 0 })}
            {numField("Uds. palet", "unidades_palet", { step: "1", min: 0 })}
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Palet retornable</label>
              <button
                onClick={() => updateField("palet_retornable", !product.palet_retornable)}
                className={`px-3 py-1.5 rounded text-xs font-medium border transition-colors ${
                  product.palet_retornable
                    ? "bg-green-100 text-green-700 border-green-300"
                    : "bg-gray-100 text-gray-500 border-gray-300"
                }`}
              >
                {product.palet_retornable ? "Sí" : "No"}
              </button>
            </div>
          </div>
        </section>

        {/* Editable prices */}
        <section>
          <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Precios editables
          </h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {numField("PVP Proveedor", "pvp_proveedor", { min: 0 })}
            {numField("Desc. Prov. 1 (%)", "desc_prov_1", { min: 0 })}
            {numField("Coste transporte", "coste_transporte", { min: 0 })}
            {numField("IVA (%)", "iva_porcentaje", { min: 0 })}
            {numField("Desc. Camión VIP (%)", "desc_camion_vip", { min: 0 })}
            {numField("Desc. Camión (%)", "desc_camion", { min: 0 })}
            {numField("Desc. Oferta (%)", "desc_oferta", { min: 0 })}
            {numField("Desc. VIP (%)", "desc_vip", { min: 0 })}
            {numField("Desc. Empresas (%)", "desc_empresas", { min: 0 })}
            {numField("Desc. Empresas A (%)", "desc_empresas_a", { min: 0 })}
          </div>
        </section>

        {/* Calculated prices */}
        <section>
          <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Precios calculados <span className="text-gray-400 font-normal">(solo lectura)</span>
          </h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {calcField("Coste neto", calculated.coste_neto)}
            {calcField("Coste neto m²", calculated.coste_neto_m2)}
            {calcField("Coste m² + trans.", calculated.coste_m2_trans)}
            {calcField("Pre PVP", calculated.pre_pvp)}
            {calcField("PVP", calculated.pvp)}
            {calcField("Neto Camión VIP", calculated.neto_camion_vip)}
            {calcField("Neto Camión", calculated.neto_camion)}
            {calcField("Neto Oferta", calculated.neto_oferta)}
            {calcField("Neto VIP", calculated.neto_vip)}
            {calcField("Neto Empresas", calculated.neto_empresas)}
            {calcField("Neto Empresas A", calculated.neto_empresas_a)}
          </div>
        </section>

        {/* Dynamic filters */}
        <section>
          <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Filtros dinámicos
          </h3>
          <div className="space-y-2">
            {Object.entries(product.filtros_dinamicos || {}).map(([key, value]) => (
              <div key={key} className="flex items-center gap-2">
                <span className="text-xs font-medium text-gray-600 w-32 truncate">{key}</span>
                <input
                  type="text"
                  value={value}
                  onChange={(e) =>
                    setProduct((prev) => ({
                      ...prev,
                      filtros_dinamicos: { ...prev.filtros_dinamicos, [key]: e.target.value },
                    }))
                  }
                  className="flex-1 border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
                />
                <button
                  onClick={() => removeFilter(key)}
                  className="p-1 text-red-500 hover:bg-red-50 rounded"
                >
                  <Trash2 size={14} />
                </button>
              </div>
            ))}
            <div className="flex items-center gap-2 pt-1">
              <input
                type="text"
                placeholder="Clave"
                value={newFilterKey}
                onChange={(e) => setNewFilterKey(e.target.value)}
                className="w-32 border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
              />
              <input
                type="text"
                placeholder="Valor"
                value={newFilterValue}
                onChange={(e) => setNewFilterValue(e.target.value)}
                className="flex-1 border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
                onKeyDown={(e) => e.key === "Enter" && addFilter()}
              />
              <button
                onClick={addFilter}
                className="p-1 text-green-600 hover:bg-green-50 rounded"
                title="Añadir filtro"
              >
                <Plus size={14} />
              </button>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}
