"use client";

import { useEffect, useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import { ChevronLeft, ChevronRight, FileText } from "lucide-react";
import { useUser } from "@/context/UserContext";
import {
  catalogService,
  type QuoteItem,
  type PaginatedResponse,
} from "@/services/catalog.service";

// ─── Pagination ───

function Pagination({
  currentPage,
  lastPage,
  onPageChange,
}: {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
}) {
  if (lastPage <= 1) return null;

  const getPages = (): (number | "...")[] => {
    const pages: (number | "...")[] = [];
    const delta = 2;
    const left = Math.max(2, currentPage - delta);
    const right = Math.min(lastPage - 1, currentPage + delta);

    pages.push(1);
    if (left > 2) pages.push("...");
    for (let i = left; i <= right; i++) pages.push(i);
    if (right < lastPage - 1) pages.push("...");
    if (lastPage > 1) pages.push(lastPage);

    return pages;
  };

  return (
    <div className="flex items-center justify-center gap-1 mt-6">
      <button
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage <= 1}
        className="p-2 rounded-lg text-gray-500 hover:bg-[#F5F5F5] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
        aria-label="Página anterior"
      >
        <ChevronLeft size={18} />
      </button>
      {getPages().map((page, i) =>
        page === "..." ? (
          <span key={`dots-${i}`} className="px-2 text-gray-400 text-sm">
            …
          </span>
        ) : (
          <button
            key={page}
            onClick={() => onPageChange(page)}
            className={`min-w-[36px] h-9 rounded-lg text-sm font-medium transition-colors ${
              page === currentPage
                ? "bg-[#E8751A] text-white"
                : "text-[#333333] hover:bg-[#F5F5F5]"
            }`}
          >
            {page}
          </button>
        )
      )}
      <button
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage >= lastPage}
        className="p-2 rounded-lg text-gray-500 hover:bg-[#F5F5F5] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
        aria-label="Página siguiente"
      >
        <ChevronRight size={18} />
      </button>
    </div>
  );
}

// ─── QuoteTable ───

function QuoteTable({
  quotes,
  onProductClick,
}: {
  quotes: QuoteItem[];
  onProductClick: (slug: string) => void;
}) {
  const formatPrice = (price: number) =>
    new Intl.NumberFormat("es-ES", {
      style: "currency",
      currency: "EUR",
    }).format(price);

  const formatDate = (iso: string) =>
    new Intl.DateTimeFormat("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }).format(new Date(iso));

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200">
      <table className="w-full text-sm">
        <thead>
          <tr className="bg-[#F5F5F5] border-b border-gray-200">
            <th className="text-left px-4 py-3 font-semibold text-[#333333]">Fecha</th>
            <th className="text-left px-4 py-3 font-semibold text-[#333333]">Producto</th>
            <th className="text-right px-4 py-3 font-semibold text-[#333333]">m²</th>
            <th className="text-right px-4 py-3 font-semibold text-[#333333]">Total</th>
          </tr>
        </thead>
        <tbody>
          {quotes.map((q) => (
            <tr
              key={q.id}
              className="border-b border-gray-100 hover:bg-[#F5F5F5]/60 transition-colors"
            >
              <td className="px-4 py-3 text-gray-500">{formatDate(q.fecha)}</td>
              <td className="px-4 py-3">
                {q.producto_slug ? (
                  <button
                    onClick={() => onProductClick(q.producto_slug!)}
                    className="font-medium text-[#E8751A] hover:underline text-left"
                  >
                    {q.producto ?? "—"}
                  </button>
                ) : (
                  <span className="font-medium text-[#333333]">{q.producto ?? "—"}</span>
                )}
              </td>
              <td className="px-4 py-3 text-right text-gray-500">
                {q.m2.toFixed(2)}
              </td>
              <td className="px-4 py-3 text-right font-semibold text-[#E8751A]">
                {formatPrice(q.total)}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ─── EmptyState ───

function EmptyState() {
  return (
    <div className="flex flex-col items-center justify-center py-16 gap-4">
      <FileText size={48} className="text-gray-300" />
      <p className="text-gray-500 text-center">
        Aún no has generado ningún presupuesto
      </p>
      <a
        href="/app/productos"
        className="px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors hover:opacity-90"
        style={{ backgroundColor: "#E8751A" }}
      >
        Explorar productos
      </a>
    </div>
  );
}

// ─── LoadingSkeleton ───

function LoadingSkeleton() {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-8">
      <div className="flex flex-col gap-3">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="h-12 bg-[#F5F5F5] rounded-lg animate-pulse" />
        ))}
      </div>
    </div>
  );
}

// ─── QuoteHistoryPage (main) ───

export default function QuoteHistoryPage() {
  const router = useRouter();
  const { user } = useUser();

  const [result, setResult] = useState<PaginatedResponse<QuoteItem> | null>(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  const fetchQuotes = useCallback((p: number) => {
    setLoading(true);
    catalogService
      .getQuotes(p)
      .then(setResult)
      .catch(() => setResult(null))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    fetchQuotes(page);
  }, [page, fetchQuotes]);

  const handlePageChange = useCallback((p: number) => {
    setPage(p);
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, []);

  const handleProductClick = useCallback(
    (slug: string) => {
      router.push(`/app/productos/${slug}`);
    },
    [router]
  );

  const quotes = result?.data ?? [];
  const total = result?.total ?? 0;

  return (
    <div className="min-h-[60vh] bg-[#F5F5F5]">
      {/* Header */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
          <h1 className="text-xl sm:text-2xl font-bold text-[#333333]">
            Mis Presupuestos
          </h1>
          {user && (
            <p className="text-sm text-gray-500 mt-0.5">{user.name}</p>
          )}
        </div>
      </div>

      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {/* Results counter */}
        {!loading && total > 0 && (
          <p className="text-sm font-semibold text-[#333333] uppercase tracking-wide mb-4">
            {total} presupuesto{total !== 1 ? "s" : ""}
          </p>
        )}

        {/* Content */}
        {loading ? (
          <LoadingSkeleton />
        ) : quotes.length === 0 ? (
          <div className="bg-white rounded-xl border border-gray-200">
            <EmptyState />
          </div>
        ) : (
          <>
            <QuoteTable quotes={quotes} onProductClick={handleProductClick} />
            <Pagination
              currentPage={result?.current_page ?? 1}
              lastPage={result?.last_page ?? 1}
              onPageChange={handlePageChange}
            />
          </>
        )}
      </div>
    </div>
  );
}
