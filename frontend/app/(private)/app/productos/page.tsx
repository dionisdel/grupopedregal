"use client";

import { useEffect, useState, useCallback } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { ChevronDown, ChevronLeft, ChevronRight, X, SlidersHorizontal, Package } from "lucide-react";
import { useUser } from "@/context/UserContext";
import {
  catalogService,
  type Category,
  type ClientProduct,
  type FilterOption,
  type CatalogFilters,
  type PaginatedResponse,
} from "@/services/catalog.service";

// ─── CategorySidebar ───

function CategorySidebar({
  categories,
  activeCategoryId,
  onSelect,
}: {
  categories: Category[];
  activeCategoryId: number | null;
  onSelect: (cat: Category | null) => void;
}) {
  return (
    <aside className="w-full">
      <h2 className="text-sm font-bold text-[#333333] uppercase tracking-wider mb-3 px-1">
        Categorías
      </h2>
      <nav className="flex flex-col gap-0.5">
        <button
          onClick={() => onSelect(null)}
          className={`text-left text-sm px-3 py-2 rounded-lg transition-colors ${
            activeCategoryId === null
              ? "bg-[#E8751A] text-white font-semibold"
              : "text-[#333333] hover:bg-[#F5F5F5]"
          }`}
        >
          Todas las categorías
        </button>
        {categories.map((cat) => (
          <button
            key={cat.id}
            onClick={() => onSelect(cat)}
            className={`text-left text-sm px-3 py-2 rounded-lg transition-colors ${
              activeCategoryId === cat.id
                ? "bg-[#E8751A] text-white font-semibold"
                : "text-[#333333] hover:bg-[#F5F5F5]"
            }`}
          >
            {cat.nombre}
          </button>
        ))}
      </nav>
    </aside>
  );
}

// ─── FilterDropdown ───

function FilterDropdown({
  label,
  options,
  value,
  onChange,
}: {
  label: string;
  options: FilterOption[];
  value: number | null;
  onChange: (id: number | null) => void;
}) {
  return (
    <div className="flex flex-col gap-1 min-w-[160px]">
      <label className="text-xs font-medium text-gray-500 uppercase tracking-wide">
        {label}
      </label>
      <div className="relative">
        <select
          value={value ?? ""}
          onChange={(e) => onChange(e.target.value ? Number(e.target.value) : null)}
          className="w-full appearance-none bg-white border border-gray-200 rounded-lg px-3 py-2 pr-8 text-sm text-[#333333] focus:outline-none focus:border-[#E8751A] focus:ring-1 focus:ring-[#E8751A]/30 transition-colors"
        >
          <option value="">Todos</option>
          {options.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.nombre}
            </option>
          ))}
        </select>
        <ChevronDown
          size={14}
          className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
        />
      </div>
    </div>
  );
}

// ─── ProductFilters ───

function ProductFilters({
  subcategories,
  brands,
  suppliers,
  filters,
  onFilterChange,
}: {
  subcategories: FilterOption[];
  brands: FilterOption[];
  suppliers: FilterOption[];
  filters: CatalogFilters;
  onFilterChange: (key: keyof CatalogFilters, value: number | null | string) => void;
}) {
  return (
    <div className="flex flex-wrap items-end gap-4">
      <FilterDropdown
        label="Subcategoría"
        options={subcategories}
        value={filters.subcategory_id ?? null}
        onChange={(v) => onFilterChange("subcategory_id", v)}
      />
      <FilterDropdown
        label="Marca"
        options={brands}
        value={filters.brand_id ?? null}
        onChange={(v) => onFilterChange("brand_id", v)}
      />
      <FilterDropdown
        label="Proveedor"
        options={suppliers}
        value={filters.supplier_id ?? null}
        onChange={(v) => onFilterChange("supplier_id", v)}
      />
    </div>
  );
}

// ─── ResultsCounter ───

function ResultsCounter({ total }: { total: number }) {
  return (
    <p className="text-sm font-semibold text-[#333333] uppercase tracking-wide">
      Mostrando {total} resultado{total !== 1 ? "s" : ""}
    </p>
  );
}

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

// ─── ClientProductTable ───

function ClientProductTable({
  products,
  onProductClick,
}: {
  products: ClientProduct[];
  onProductClick: (slug: string) => void;
}) {
  const formatPrice = (price: number | null) => {
    if (price === null || price === undefined) return "—";
    return new Intl.NumberFormat("es-ES", {
      style: "currency",
      currency: "EUR",
    }).format(price);
  };

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200">
      <table className="w-full text-sm">
        <thead>
          <tr className="bg-[#F5F5F5] border-b border-gray-200">
            <th className="text-left px-4 py-3 font-semibold text-[#333333]">Nombre</th>
            <th className="text-left px-4 py-3 font-semibold text-[#333333] hidden sm:table-cell">
              Categoría
            </th>
            <th className="text-left px-4 py-3 font-semibold text-[#333333] hidden md:table-cell">
              Marca
            </th>
            <th className="text-left px-4 py-3 font-semibold text-[#333333] hidden lg:table-cell">
              Unidad
            </th>
            <th className="text-right px-4 py-3 font-semibold text-[#333333]">Precio Tarifa</th>
          </tr>
        </thead>
        <tbody>
          {products.map((product) => (
            <tr
              key={product.id}
              onClick={() => onProductClick(product.slug)}
              className="border-b border-gray-100 hover:bg-[#F5F5F5]/60 cursor-pointer transition-colors"
            >
              <td className="px-4 py-3 font-medium text-[#333333]">{product.nombre}</td>
              <td className="px-4 py-3 text-gray-500 hidden sm:table-cell">
                {product.categoria ?? "—"}
              </td>
              <td className="px-4 py-3 text-gray-500 hidden md:table-cell">
                {product.marca ?? "—"}
              </td>
              <td className="px-4 py-3 text-gray-500 hidden lg:table-cell">
                {product.unidad ?? "—"}
              </td>
              <td className="px-4 py-3 text-right font-semibold text-[#E8751A]">
                {formatPrice(product.precio)}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ─── EmptyState ───

function EmptyState({ onClear }: { onClear: () => void }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 gap-4">
      <Package size={48} className="text-gray-300" />
      <p className="text-gray-500 text-center">
        No se encontraron productos con los filtros seleccionados
      </p>
      <button
        onClick={onClear}
        className="px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors hover:opacity-90"
        style={{ backgroundColor: "#E8751A" }}
      >
        Limpiar filtros
      </button>
    </div>
  );
}

// ─── MobileCategorySidebar ───

function MobileCategorySidebar({
  categories,
  activeCategoryId,
  onSelect,
  open,
  onClose,
}: {
  categories: Category[];
  activeCategoryId: number | null;
  onSelect: (cat: Category | null) => void;
  open: boolean;
  onClose: () => void;
}) {
  if (!open) return null;

  return (
    <>
      <div
        className="fixed inset-0 bg-black/40 z-40"
        onClick={onClose}
        aria-hidden="true"
      />
      <div className="fixed inset-y-0 left-0 w-[280px] bg-white z-50 shadow-xl p-5 overflow-y-auto">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-sm font-bold text-[#333333] uppercase tracking-wider">
            Categorías
          </h2>
          <button onClick={onClose} aria-label="Cerrar categorías">
            <X size={20} className="text-gray-500" />
          </button>
        </div>
        <CategorySidebar
          categories={categories}
          activeCategoryId={activeCategoryId}
          onSelect={(cat) => {
            onSelect(cat);
            onClose();
          }}
        />
      </div>
    </>
  );
}

// ─── ClientProductExplorer (main) ───

export default function ClientProductExplorerPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { user } = useUser();

  // State
  const [categories, setCategories] = useState<Category[]>([]);
  const [activeCategory, setActiveCategory] = useState<Category | null>(null);
  const [filters, setFilters] = useState<CatalogFilters>({});
  const [subcategories, setSubcategories] = useState<FilterOption[]>([]);
  const [brands, setBrands] = useState<FilterOption[]>([]);
  const [suppliers, setSuppliers] = useState<FilterOption[]>([]);
  const [result, setResult] = useState<PaginatedResponse<ClientProduct> | null>(null);
  const [loading, setLoading] = useState(true);
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  // Load categories on mount (reuse public filter endpoint)
  useEffect(() => {
    catalogService.getPublicCategories().then(setCategories).catch(() => {});
  }, []);

  // Resolve initial category from URL ?category=slug
  useEffect(() => {
    if (categories.length === 0) return;
    const slug = searchParams.get("category");
    if (slug) {
      const found = categories.find((c) => c.slug === slug);
      if (found) {
        setActiveCategory(found);
        setFilters((prev) => ({ ...prev, category_id: found.id, page: 1 }));
        return;
      }
    }
    setFilters((prev) => ({ ...prev, page: 1 }));
  }, [categories, searchParams]);

  // Fetch filter options when category changes (reuse public filters endpoint)
  useEffect(() => {
    catalogService
      .getFilters(filters.category_id)
      .then((data) => {
        setSubcategories(data.subcategories);
        setBrands(data.brands);
        setSuppliers(data.suppliers);
      })
      .catch(() => {});
  }, [filters.category_id]);

  // Fetch client products when filters change
  useEffect(() => {
    setLoading(true);
    catalogService
      .getClientProducts(filters)
      .then(setResult)
      .catch(() => setResult(null))
      .finally(() => setLoading(false));
  }, [filters]);

  const handleCategorySelect = useCallback(
    (cat: Category | null) => {
      setActiveCategory(cat);
      setFilters({
        category_id: cat?.id ?? undefined,
        page: 1,
      });
      if (cat) {
        router.replace(`/app/productos?category=${cat.slug}`, { scroll: false });
      } else {
        router.replace("/app/productos", { scroll: false });
      }
    },
    [router]
  );

  const handleFilterChange = useCallback(
    (key: keyof CatalogFilters, value: number | null | string) => {
      setFilters((prev) => ({ ...prev, [key]: value, page: 1 }));
    },
    []
  );

  const handlePageChange = useCallback((page: number) => {
    setFilters((prev) => ({ ...prev, page }));
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, []);

  const handleClearFilters = useCallback(() => {
    setActiveCategory(null);
    setFilters({ page: 1 });
    router.replace("/app/productos", { scroll: false });
  }, [router]);

  const handleProductClick = useCallback(
    (slug: string) => {
      router.push(`/app/productos/${slug}`);
    },
    [router]
  );

  const products = result?.data ?? [];
  const total = result?.total ?? 0;

  return (
    <div className="min-h-[60vh] bg-[#F5F5F5]">
      {/* Header with user info */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between gap-4">
          <div>
            <h1 className="text-xl sm:text-2xl font-bold text-[#333333]">Productos</h1>
            {user && (
              <p className="text-sm text-gray-500 mt-0.5">
                {user.name}
                {(user as any).tipo_tarifa && (
                  <span className="ml-2 inline-block bg-[#E8751A]/10 text-[#E8751A] text-xs font-semibold px-2 py-0.5 rounded">
                    Tarifa: {(user as any).tipo_tarifa}
                  </span>
                )}
              </p>
            )}
          </div>
          <button
            onClick={() => setMobileSidebarOpen(true)}
            className="lg:hidden flex items-center gap-1.5 text-sm text-[#333333] border border-gray-200 rounded-lg px-3 py-2 hover:bg-[#F5F5F5] transition-colors"
          >
            <SlidersHorizontal size={16} />
            Categorías
          </button>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex gap-6">
          {/* Desktop sidebar */}
          <div className="hidden lg:block w-56 shrink-0">
            <div className="sticky top-6 bg-white rounded-xl border border-gray-200 p-4">
              <CategorySidebar
                categories={categories}
                activeCategoryId={activeCategory?.id ?? null}
                onSelect={handleCategorySelect}
              />
            </div>
          </div>

          {/* Main panel */}
          <div className="flex-1 min-w-0 flex flex-col gap-5">
            {/* Filters */}
            <div className="bg-white rounded-xl border border-gray-200 p-4">
              <ProductFilters
                subcategories={subcategories}
                brands={brands}
                suppliers={suppliers}
                filters={filters}
                onFilterChange={handleFilterChange}
              />
            </div>

            {/* Results counter */}
            <ResultsCounter total={total} />

            {/* Content */}
            {loading ? (
              <div className="bg-white rounded-xl border border-gray-200 p-8">
                <div className="flex flex-col gap-3">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <div key={i} className="h-12 bg-[#F5F5F5] rounded-lg animate-pulse" />
                  ))}
                </div>
              </div>
            ) : products.length === 0 ? (
              <div className="bg-white rounded-xl border border-gray-200">
                <EmptyState onClear={handleClearFilters} />
              </div>
            ) : (
              <>
                <ClientProductTable products={products} onProductClick={handleProductClick} />
                <Pagination
                  currentPage={result?.current_page ?? 1}
                  lastPage={result?.last_page ?? 1}
                  onPageChange={handlePageChange}
                />
              </>
            )}
          </div>
        </div>
      </div>

      {/* Mobile sidebar drawer */}
      <MobileCategorySidebar
        categories={categories}
        activeCategoryId={activeCategory?.id ?? null}
        onSelect={handleCategorySelect}
        open={mobileSidebarOpen}
        onClose={() => setMobileSidebarOpen(false)}
      />
    </div>
  );
}
