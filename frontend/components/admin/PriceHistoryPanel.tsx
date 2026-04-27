"use client";

import { useState, useEffect } from "react";
import { History } from "lucide-react";
import { adminService } from "@/services/admin.service";

interface PriceHistoryRecord {
  field_changed: string;
  old_value: number | null;
  new_value: number | null;
  changed_at: string;
  changed_by: string | null;
}

interface PriceHistoryPanelProps {
  productId: number;
}

const fieldLabels: Record<string, string> = {
  pvp_proveedor: "PVP Proveedor",
  desc_prov_1: "Desc. Prov. 1",
  coste_transporte: "Coste Transporte",
  iva_porcentaje: "IVA %",
  desc_camion_vip: "Desc. Camión VIP",
  desc_camion: "Desc. Camión",
  desc_oferta: "Desc. Oferta",
  desc_vip: "Desc. VIP",
  desc_empresas: "Desc. Empresas",
  desc_empresas_a: "Desc. Empresas A",
};

export default function PriceHistoryPanel({ productId }: PriceHistoryPanelProps) {
  const [records, setRecords] = useState<PriceHistoryRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    adminService
      .getPriceHistory(productId)
      .then((data) => setRecords(data))
      .catch(() => setError("Error al cargar historial"))
      .finally(() => setLoading(false));
  }, [productId]);

  const formatDate = (dateStr: string) => {
    try {
      return new Date(dateStr).toLocaleString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    } catch {
      return dateStr;
    }
  };

  const formatValue = (val: number | null) =>
    val != null ? val.toFixed(4) : "—";

  return (
    <div className="bg-white border border-gray-200 rounded-xl">
      <div className="flex items-center gap-2 px-4 py-3 border-b bg-gray-50 rounded-t-xl">
        <History size={16} className="text-[#E8751A]" />
        <h3 className="text-sm font-semibold text-[#333]">Historial de precios</h3>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-8 text-sm text-gray-400">
          Cargando...
        </div>
      ) : error ? (
        <div className="px-4 py-4 text-sm text-red-600">{error}</div>
      ) : records.length === 0 ? (
        <div className="flex items-center justify-center py-8 text-sm text-gray-400">
          Sin cambios registrados
        </div>
      ) : (
        <div className="overflow-auto max-h-80">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-gray-50/50">
                <th className="text-left px-3 py-2 text-xs font-semibold text-gray-500">Campo</th>
                <th className="text-right px-3 py-2 text-xs font-semibold text-gray-500">Anterior</th>
                <th className="text-right px-3 py-2 text-xs font-semibold text-gray-500">Nuevo</th>
                <th className="text-left px-3 py-2 text-xs font-semibold text-gray-500">Fecha</th>
                <th className="text-left px-3 py-2 text-xs font-semibold text-gray-500">Usuario</th>
              </tr>
            </thead>
            <tbody>
              {records.map((rec, i) => (
                <tr key={i} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="px-3 py-2 text-[#333] font-medium">
                    {fieldLabels[rec.field_changed] || rec.field_changed}
                  </td>
                  <td className="px-3 py-2 text-right text-gray-500 font-mono text-xs">
                    {formatValue(rec.old_value)}
                  </td>
                  <td className="px-3 py-2 text-right text-[#E8751A] font-mono text-xs font-medium">
                    {formatValue(rec.new_value)}
                  </td>
                  <td className="px-3 py-2 text-gray-500 text-xs">
                    {formatDate(rec.changed_at)}
                  </td>
                  <td className="px-3 py-2 text-gray-500 text-xs">
                    {rec.changed_by || "Sistema"}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
