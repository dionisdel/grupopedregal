"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Package } from "lucide-react";
import { categoryService } from "@/services/category.service";
import type { CategoryNode, ProductListItem } from "@/services/types";
import Breadcrumb from "@/components/Breadcrumb";
import FilterChips from "@/components/FilterChips";
import ProductGrid from "@/components/ProductGrid";
import { useCart } from "@/context/CartContext";

function CategoryCircleCard({ category, basePath }: { category: CategoryNode; basePath: string }) {
  const imgSrc = category.imagen_thumbnail_url
    ? `${process.env.NEXT_PUBLIC_API_URL || ""}${category.imagen_thumbnail_url}`
    : null;

  return (
    <Link
      href={`${basePath}/${category.slug}`}
      className="group flex flex-col items-center gap-3 text-center"
    >
      <div className="w-24 h-24 sm:w-28 sm:h-28 rounded-full overflow-hidden border-2 border-gray-200 group-hover:border-[#E8751A] transition-colors bg-[#F5F5F5]">
        {imgSrc ? (
          <img src={imgSrc} alt={category.nombre} className="w-full h-full object-cover" />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <Package size={32} className="text-gray-300" />
          </div>
        )}
      </div>
      <span className="text-sm font-medium text-[#333] group-hover:text-[#E8751A] transition-colors">
        {category.nombre}
      </span>
    </Link>
  );
}

export default function CategoryPageClient({ params }: { params: Promise<{ slug?: string[] }> }) {
  const pathname = usePathname();
  // Extract slug from pathname, handling both local and production paths
  // Local: /categorias/a/b/c → ['a', 'b', 'c']
  // Production: /backend/public/categorias/a/b/c → ['a', 'b', 'c']
  const catIndex = pathname.indexOf('/categorias');
  const catPath = catIndex >= 0 ? pathname.substring(catIndex) : pathname;
  const slugArray = catPath
    .replace(/^\/categorias\/?/, '')
    .split('/')
    .filter(Boolean);
  const path = slugArray.join("/");
  const { addItem } = useCart();
  const [toastProduct, setToastProduct] = useState<string | null>(null);

  const handleAddToCart = (product: ProductListItem) => {
    addItem({
      id: product.id,
      nombre: product.nombre,
      slug: product.slug,
      imagen_url: product.imagen_url,
      pvp: product.pvp,
    });
    setToastProduct(product.nombre);
    setTimeout(() => setToastProduct(null), 2000);
  };

  const [category, setCategory] = useState<CategoryNode | null>(null);
  const [breadcrumb, setBreadcrumb] = useState<{ id: number; nombre: string; slug: string }[]>([]);
  const [siblings, setSiblings] = useState<CategoryNode[]>([]);
  const [products, setProducts] = useState<ProductListItem[]>([]);
  const [availableFilters, setAvailableFilters] = useState<Record<string, string[]>>({});
  const [selectedFilters, setSelectedFilters] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [productsLoading, setProductsLoading] = useState(false);
  const [rootCategories, setRootCategories] = useState<CategoryNode[]>([]);

  useEffect(() => {
    if (!path) {
      setLoading(true);
      categoryService.fetchTree()
        .then((tree) => {
          setRootCategories(tree.filter((c) => c.parent_id === null));
          setCategory(null);
          setBreadcrumb([]);
          setSiblings([]);
          setProducts([]);
          setAvailableFilters({});
        })
        .catch(() => setRootCategories([]))
        .finally(() => setLoading(false));
      return;
    }

    setLoading(true);
    categoryService.fetchByPath(path)
      .then((res) => {
        setCategory(res.category);
        setBreadcrumb(res.breadcrumb || []);
        setSiblings(res.siblings || []);
        setSelectedFilters({});
      })
      .catch(() => setCategory(null))
      .finally(() => setLoading(false));
  }, [path]);

  const isLeaf = category && (!category.children || category.children.length === 0);

  useEffect(() => {
    if (!category || !isLeaf) {
      setProducts([]);
      setAvailableFilters({});
      return;
    }

    setProductsLoading(true);
    Promise.all([
      categoryService.fetchProducts(category.id, 1, selectedFilters),
      categoryService.fetchFilters(category.id),
    ])
      .then(([productsRes, filtersRes]) => {
        setProducts(productsRes.data || []);
        if (Object.keys(selectedFilters).length === 0) {
          setAvailableFilters(filtersRes);
        }
      })
      .catch(() => setProducts([]))
      .finally(() => setProductsLoading(false));
  }, [category?.id, isLeaf, selectedFilters]);

  const breadcrumbItems = breadcrumb.map((item, i) => {
    const slugPath = breadcrumb.slice(0, i + 1).map((b) => b.slug).join("/");
    const isLast = i === breadcrumb.length - 1;
    return { label: item.nombre, href: isLast ? undefined : `/categorias/${slugPath}` };
  });

  const filteredProducts = products.filter((p) =>
    Object.entries(selectedFilters).every(
      ([key, value]) => p.filtros_dinamicos && p.filtros_dinamicos[key] === value,
    ),
  );

  if (loading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="animate-pulse space-y-6">
          <div className="h-6 bg-gray-200 rounded w-1/3" />
          <div className="h-48 bg-gray-200 rounded" />
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="h-40 bg-gray-200 rounded" />
            ))}
          </div>
        </div>
      </div>
    );
  }

  if (!path) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 className="text-2xl sm:text-3xl font-bold text-[#333] mb-3">Productos</h1>
        <p className="text-gray-500 mb-10">Explora nuestra amplia gama de productos organizados por categoría</p>
        {rootCategories.length === 0 ? (
          <p className="text-center text-gray-400">No hay categorías disponibles.</p>
        ) : (
          <div className="flex flex-wrap gap-8 sm:gap-12 justify-center">
            {rootCategories.map((cat) => (
              <CategoryCircleCard key={cat.id} category={cat} basePath="/categorias" />
            ))}
          </div>
        )}
      </div>
    );
  }

  if (!category) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
        <h1 className="text-xl font-bold text-[#333] mb-4">Categoría no encontrada</h1>
        <p className="text-gray-500 mb-6">La categoría que buscas no existe.</p>
        <Link href="/" className="text-[#E8751A] font-medium hover:underline">Volver al inicio</Link>
      </div>
    );
  }

  const bannerSrc = category.imagen_banner_url
    ? `${process.env.NEXT_PUBLIC_API_URL || ""}${category.imagen_banner_url}`
    : null;

  if (!isLeaf) {
    return (
      <div>
        {bannerSrc && (
          <div className="w-full h-48 sm:h-64 bg-[#F5F5F5] overflow-hidden">
            <img src={bannerSrc} alt={category.nombre} className="w-full h-full object-cover" />
          </div>
        )}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <Breadcrumb items={breadcrumbItems} />
          <h1 className="text-2xl sm:text-3xl font-bold text-[#333] mt-4 mb-2">{category.nombre}</h1>
          {category.descripcion && (
            <p className="text-gray-500 mb-8 max-w-2xl">{category.descripcion}</p>
          )}
          <div className="flex flex-wrap gap-8 sm:gap-12 justify-center sm:justify-start">
            {category.children.map((child) => (
              <CategoryCircleCard key={child.id} category={child} basePath={`/categorias/${path}`} />
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <Breadcrumb items={breadcrumbItems} />
      <h1 className="text-2xl sm:text-3xl font-bold text-[#333] mt-4 mb-4">{category.nombre}</h1>

      {siblings.length > 1 && (
        <div className="flex flex-wrap gap-2 mb-4">
          {siblings.map((sib) => {
            const sibPath = breadcrumb.length > 1
              ? breadcrumb.slice(0, -1).map((b) => b.slug).join("/") + "/" + sib.slug
              : sib.slug;
            const isCurrent = sib.id === category.id;
            return (
              <Link
                key={sib.id}
                href={`/categorias/${sibPath}`}
                className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-colors ${
                  isCurrent
                    ? "bg-[#E8751A] text-white border-[#E8751A]"
                    : "bg-white text-[#333] border-gray-300 hover:border-[#E8751A] hover:text-[#E8751A]"
                }`}
              >
                {sib.nombre}
              </Link>
            );
          })}
        </div>
      )}

      {Object.keys(availableFilters).length > 0 && (
        <div className="mb-6">
          <FilterChips
            availableFilters={availableFilters}
            selectedFilters={selectedFilters}
            onChange={setSelectedFilters}
          />
        </div>
      )}

      <ProductGrid products={filteredProducts} loading={productsLoading} onAdd={handleAddToCart} />

      {/* Toast notification */}
      {toastProduct && (
        <div className="fixed bottom-6 right-6 z-50 bg-[#333] text-white px-4 py-3 rounded-lg shadow-lg text-sm animate-fade-in">
          ✓ {toastProduct} añadido al carrito
        </div>
      )}
    </div>
  );
}
