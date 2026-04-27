"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { useUser } from "@/context/UserContext";
import api from "@/services/axios-instance";
import CategoryTreeEditor from "@/components/admin/CategoryTreeEditor";
import ProductList from "@/components/admin/ProductList";
import ProductEditor from "@/components/admin/ProductEditor";
import ExcelImporter from "@/components/admin/ExcelImporter";
import PriceHistoryPanel from "@/components/admin/PriceHistoryPanel";
import type { CategoryNode } from "@/services/types";

type View = "list" | "editor" | "import";

export default function AdminProductsPage() {
  const { user, loading } = useUser();
  const router = useRouter();
  const [selectedCategory, setSelectedCategory] = useState<CategoryNode | null>(null);
  const [view, setView] = useState<View>("list");
  const [editingProductId, setEditingProductId] = useState<number | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  // Role guard
  useEffect(() => {
    if (loading) return;
    const roleSlug = user?.role?.slug;
    if (!roleSlug || (roleSlug !== "admin" && roleSlug !== "superadmin")) {
      router.replace("/login");
    }
  }, [user, loading, router]);

  // Handle browser back button
  useEffect(() => {
    const onPopState = () => {
      setEditingProductId(null);
      setView("list");
    };
    window.addEventListener("popstate", onPopState);
    return () => window.removeEventListener("popstate", onPopState);
  }, []);

  const roleSlug = user?.role?.slug;
  const isAdmin = roleSlug === "admin" || roleSlug === "superadmin";

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh] text-gray-400">
        Cargando...
      </div>
    );
  }

  if (!isAdmin) return null;

  const handleSelectProduct = (productId: number) => {
    setEditingProductId(productId);
    setView("editor");
    window.history.pushState(null, "", `/admin/productos/${productId}/`);
  };

  const handleNewProduct = () => {
    setEditingProductId(null);
    setView("editor");
    window.history.pushState(null, "", "/admin/productos/nuevo/");
  };

  const handleEditorClose = () => {
    setEditingProductId(null);
    setView("list");
    window.history.pushState(null, "", "/admin/productos/");
  };

  const handleSaved = () => {
    setRefreshKey((k) => k + 1);
  };

  return (
    <div className="min-h-[calc(100vh-4rem)] bg-[#F5F5F5]">
      {/* Top bar */}
      <div className="bg-white border-b px-4 sm:px-6 py-3 flex items-center justify-between">
        <h1 className="text-lg font-bold text-[#333]">Gestión de Productos</h1>
        <div className="flex items-center gap-2">
          <button
            onClick={() => setView("list")}
            className={`px-3 py-1.5 text-xs font-medium rounded transition-colors ${
              view === "list"
                ? "bg-[#E8751A] text-white"
                : "bg-gray-100 text-gray-600 hover:bg-gray-200"
            }`}
          >
            Productos
          </button>
          <button
            onClick={() => setView("import")}
            className={`px-3 py-1.5 text-xs font-medium rounded transition-colors ${
              view === "import"
                ? "bg-[#E8751A] text-white"
                : "bg-gray-100 text-gray-600 hover:bg-gray-200"
            }`}
          >
            Importar Excel
          </button>
          <button
            onClick={async () => {
              try {
                const res = await api.get("/api/admin/products/export", { responseType: "blob" });
                const url = URL.createObjectURL(res.data);
                const a = document.createElement("a");
                a.href = url;
                a.download = new Date().toISOString().slice(0,10).replace(/-/g,"") + "-productos.xlsx";
                a.click();
                URL.revokeObjectURL(url);
              } catch (e) { console.error("Export failed", e); }
            }}
            className="px-3 py-1.5 text-xs font-medium rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
          >
            Exportar Excel
          </button>
        </div>
      </div>

      {/* Main content */}
      <div className="flex h-[calc(100vh-8rem)]">
        {/* Left: Category tree */}
        <div className="w-72 xl:w-80 border-r bg-white shrink-0 hidden md:flex flex-col">
          <CategoryTreeEditor
            onSelectCategory={setSelectedCategory}
            selectedCategoryId={selectedCategory?.id ?? null}
          />
        </div>

        {/* Right: Content area */}
        <div className="flex-1 flex flex-col overflow-hidden">
          {view === "import" ? (
            <div className="p-6 overflow-auto">
              <ExcelImporter onImportComplete={handleSaved} />
            </div>
          ) : view === "editor" ? (
            <div className="flex flex-col h-full overflow-hidden">
              <ProductEditor
                productId={editingProductId}
                categoryId={selectedCategory?.id ?? null}
                onClose={handleEditorClose}
                onSaved={handleSaved}
              />
              {editingProductId && (
                <div className="border-t p-4 overflow-auto max-h-64 shrink-0">
                  <PriceHistoryPanel productId={editingProductId} />
                </div>
              )}
            </div>
          ) : (
            <ProductList
              categoryId={selectedCategory?.id ?? null}
              onSelectProduct={handleSelectProduct}
              onNewProduct={handleNewProduct}
              refreshKey={refreshKey}
            />
          )}
        </div>
      </div>
    </div>
  );
}
