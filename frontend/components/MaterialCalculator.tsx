"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { Calculator, Loader2 } from "lucide-react";
import {
  catalogService,
  type CalculatorResult,
} from "@/services/catalog.service";

// ─── Price formatter ───

function formatPrice(value: number | null | undefined): string {
  if (value === null || value === undefined) return "—";
  return new Intl.NumberFormat("es-ES", {
    style: "currency",
    currency: "EUR",
  }).format(value);
}

// ─── Props ───

interface MaterialCalculatorProps {
  productId: number;
  productName: string;
  onStateChange?: (m2: number | null, merma: number) => void;
}

// ─── Component ───

export default function MaterialCalculator({
  productId,
  productName,
  onStateChange,
}: MaterialCalculatorProps) {
  const [m2, setM2] = useState<string>("");
  const [merma, setMerma] = useState<string>("5");
  const [result, setResult] = useState<CalculatorResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const calculate = useCallback(
    (m2Value: number, mermaValue: number) => {
      if (debounceRef.current) clearTimeout(debounceRef.current);

      // If m2 <= 0, show zeroed table without API call
      if (m2Value <= 0) {
        setResult({
          materiales: [],
          subtotal_sin_merma: 0,
          merma_porcentaje: mermaValue,
          total_con_merma: 0,
        });
        setLoading(false);
        setError(null);
        return;
      }

      setLoading(true);
      setError(null);

      debounceRef.current = setTimeout(async () => {
        try {
          const data = await catalogService.calculateMaterials(
            productId,
            m2Value,
            mermaValue,
          );
          setResult(data);
        } catch {
          setError("No se pudo calcular. Inténtalo de nuevo.");
        } finally {
          setLoading(false);
        }
      }, 500);
    },
    [productId],
  );

  // Trigger recalculation when m2 or merma changes
  useEffect(() => {
    const m2Num = parseFloat(m2);
    const mermaNum = parseFloat(merma);
    const effectiveMerma = isNaN(mermaNum) ? 5 : mermaNum;

    if (isNaN(m2Num) || m2 === "") {
      setResult(null);
      setLoading(false);
      onStateChange?.(null, effectiveMerma);
      return;
    }

    onStateChange?.(m2Num, effectiveMerma);
    calculate(m2Num, effectiveMerma);

    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [m2, merma, calculate]);

  const mermaAmount =
    result && result.subtotal_sin_merma > 0
      ? result.total_con_merma - result.subtotal_sin_merma
      : 0;

  return (
    <div className="bg-white rounded-xl border border-gray-200 p-6">
      {/* Header */}
      <div className="flex items-center gap-2 mb-5">
        <Calculator size={20} className="text-[#E8751A]" />
        <h2 className="text-sm font-bold text-[#333333] uppercase tracking-wider">
          Calculadora de materiales
        </h2>
      </div>

      {/* Input fields */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
        <div>
          <label
            htmlFor="calc-m2"
            className="block text-sm font-medium text-gray-600 mb-1"
          >
            m² de obra
          </label>
          <input
            id="calc-m2"
            type="number"
            min="0"
            step="0.01"
            placeholder="Ej: 50"
            value={m2}
            onChange={(e) => setM2(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors"
          />
        </div>
        <div>
          <label
            htmlFor="calc-merma"
            className="block text-sm font-medium text-gray-600 mb-1"
          >
            Merma (%)
          </label>
          <input
            id="calc-merma"
            type="number"
            min="0"
            max="100"
            step="0.5"
            value={merma}
            onChange={(e) => setMerma(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors"
          />
        </div>
      </div>

      {/* Loading state */}
      {loading && (
        <div className="flex items-center justify-center gap-2 py-8 text-gray-400">
          <Loader2 size={20} className="animate-spin" />
          <span className="text-sm">Calculando…</span>
        </div>
      )}

      {/* Error state */}
      {error && !loading && (
        <p className="text-sm text-red-500 text-center py-4">{error}</p>
      )}

      {/* Results table */}
      {result && !loading && !error && (
        <>
          <div className="overflow-x-auto">
            <table className="w-full text-sm" role="table">
              <thead>
                <tr className="border-b-2 border-gray-200">
                  <th className="text-left py-2 pr-3 font-semibold text-[#333333]">
                    Descripción Material
                  </th>
                  <th className="text-right py-2 px-3 font-semibold text-[#333333]">
                    Cantidad/m²
                  </th>
                  <th className="text-right py-2 px-3 font-semibold text-[#333333]">
                    Cantidad Total
                  </th>
                  <th className="text-left py-2 px-3 font-semibold text-[#333333]">
                    Unidad
                  </th>
                  <th className="text-right py-2 px-3 font-semibold text-[#333333]">
                    Precio/ud
                  </th>
                  <th className="text-right py-2 pl-3 font-semibold text-[#333333]">
                    Total
                  </th>
                </tr>
              </thead>
              <tbody>
                {result.materiales.length > 0 ? (
                  result.materiales.map((mat, i) => (
                    <tr
                      key={i}
                      className="border-b border-gray-100 last:border-b-0"
                    >
                      <td className="py-2 pr-3 text-gray-700">
                        {mat.descripcion}
                      </td>
                      <td className="py-2 px-3 text-right text-gray-600">
                        {mat.cantidad_por_m2}
                      </td>
                      <td className="py-2 px-3 text-right font-medium text-[#333333]">
                        {mat.cantidad_total}
                      </td>
                      <td className="py-2 px-3 text-gray-600">{mat.unidad}</td>
                      <td className="py-2 px-3 text-right text-gray-600">
                        {formatPrice(mat.precio_unitario)}
                      </td>
                      <td className="py-2 pl-3 text-right font-medium text-[#333333]">
                        {formatPrice(mat.total)}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td
                      colSpan={6}
                      className="py-4 text-center text-gray-400 italic"
                    >
                      Introduce m² de obra para ver el desglose
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Totals */}
          <div className="mt-4 pt-4 border-t border-gray-200 space-y-2">
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Subtotal sin merma</span>
              <span className="font-semibold text-[#333333]">
                {formatPrice(result.subtotal_sin_merma)}
              </span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">
                Merma aplicada ({result.merma_porcentaje}%)
              </span>
              <span className="font-semibold text-[#333333]">
                {formatPrice(mermaAmount)}
              </span>
            </div>
            <div className="flex justify-between text-sm pt-2 border-t border-gray-200">
              <span className="font-bold text-[#333333]">Total con merma</span>
              <span className="text-lg font-bold text-[#E8751A]">
                {formatPrice(result.total_con_merma)}
              </span>
            </div>
          </div>
        </>
      )}

      {/* Empty state when no m2 entered */}
      {!result && !loading && !error && (
        <p className="text-sm text-gray-400 text-center py-4 italic">
          Introduce los m² de obra para calcular las cantidades de materiales
          necesarios.
        </p>
      )}
    </div>
  );
}
