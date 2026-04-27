"use client";

import { useState, useEffect, useCallback } from "react";
import { Search, ChevronLeft, ChevronRight, Eye, EyeOff, Plus, Package } from "lucide-react";
import { adminService } from "@/services/admin.service";
import type { ProductListItem, PaginatedResponse } from "@/services/types";

interface ProductListProps {
  categoryId?: number | null;
  onSelectProduct: (productId: number) => void;
  onNewProduct: () => void;
  refreshKey?: number;
}

export default function ProductList({
  categoryId,
  onSelectProduct,
  onNewProduct,
  refreshKey,
}: ProductListProps) {
  const [data, setData] = useState<PaginatedResponse<ProductListItem> | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [searchTimeout, setSearchTimeout] = useState<ReturnType<typeof setTimeout> | null>(null);

  const loadProducts = useCallback(async () => {
    setLoading(true);
    try {
      const params: Record<string, string | number> = { page, per_page: 20 };
      if (categoryId) params.category_id = categoryId;
      if (search.trim()) params.search = search.trim();
      const res = await adminService.getProducts(params);
      setData(res);
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }, [page, categoryId, search, refreshKey]);

  useEffect(() => {
    loadProducts();
  }, [loadProducts]);

  const handleSearchChange = (value: string) => {
    setSearch(value);
    if (searchTimeout) clearTimeout(searchTimeout);
    setSearchTimeout(
      setTimeout(() => {
        setPage(1);
      }, 400)
    );
  };

  const products = data?.data || [];
  const totalPages = data?.last_page || 1;
  const total = data?.total || 0;

  return (
    <div className="flex flex-col h-full">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b bg-gray-50 shrink-0">
        <div className="flex items-center gap-3">
          <h2 className="text-sm font-semibold text-[#333]">
            Productos <span className="text-gray-400 font-normal">({total})</span>
          </h2>
        </div>
        <button
          onClick={onNewProduct}
          className="flex items-center gap-1 text-xs font-medium text-white bg-[#E8751A] px-3 py-1.5 rounded hover:opacity-90 transition-opacity"
        >
          <Plus size={14} />
          Nuevo
        </button>
      </div>

      {/* Search */}
      <div className="px-4 py-2 border-b shrink-0">
        <div className="relative">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar por nombre o código..."
            value={search}
            onChange={(e) => handleSearchChange(e.target.value)}
            className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
          />
        </div>
      </div>

      {/* Table */}
      <div className="flex-1 overflow-auto">
        {loading ? (
          <div className="flex items-center justify-center py-12 text-sm text-gray-400">
            Cargando...
          </div>
        ) : products.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-12 text-sm text-gray-400 gap-2">
            <Package size={32} className="text-gray-300" />
            Sin productos
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-gray-50/50 sticky top-0">
                <th className="text-left px-3 py-2 text-xs font-semibold text-gray-500">Código</th>
                <th className="text-left px-3 py-2 text-xs font-semibold text-gray-500">Nombre</th>
                <th className="text-right px-3 py-2 text-xs font-semibold text-gray-500">PVP</th>
                <th className="text-center px-3 py-2 text-xs font-semibold text-gray-500">Estado</th>
              </tr>
            </thead>
            <tbody>
              {products.map((p) => (
                <tr
                  key={p.id}
                  onClick={() => onSelectProduct(p.id)}
                  className="border-b border-gray-100 hover:bg-orange-50/40 cursor-pointer transition-colors"
                >
                  <td className="px-3 py-2 font-mono text-xs text-gray-600">{p.codigo_articulo}</td>
                  <td className="px-3 py-2 text-[#333]">
                    <div className="flex items-center gap-2">
                      {p.imagen_url ? (
                        <img
                          src={`${process.env.NEXT_PUBLIC_API_URL || ""}${p.imagen_url}`}
                          alt=""
                          className="w-8 h-8 object-contain rounded border"
                        />
                      ) : (
                        <div className="w-8 h-8 bg-gray-100 rounded border flex items-center justify-center">
                          <Package size={14} className="text-gray-300" />
                        </div>
                      )}
                      <span className="truncate max-w-[200px]">{p.nombre}</span>
                    </div>
                  </td>
                  <td className="px-3 py-2 text-right font-medium">{p.pvp.toFixed(2)} €</td>
                  <td className="px-3 py-2 text-center">
                    {(p as unknown as { estado_publicado?: boolean }).estado_publicado !== false ? (
                      <Eye size={14} className="inline text-green-500" />
                    ) : (
                      <EyeOff size={14} className="inline text-gray-400" />
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between px-4 py-2 border-t bg-gray-50 shrink-0">
          <span className="text-xs text-gray-500">
            Página {page} de {totalPages}
          </span>
          <div className="flex items-center gap-1">
            <button
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page <= 1}
              className="p-1.5 hover:bg-gray-200 rounded disabled:opacity-30"
            >
              <ChevronLeft size={16} />
            </button>
            <button
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              disabled={page >= totalPages}
              className="p-1.5 hover:bg-gray-200 rounded disabled:opacity-30"
            >
              <ChevronRight size={16} />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
