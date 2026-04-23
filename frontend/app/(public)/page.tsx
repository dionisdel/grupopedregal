"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Package, Calculator, FileText, Search, ArrowRight } from "lucide-react";
import Link from "next/link";
import api from "@/services/axios-instance";

// --- Types ---

interface Category {
  id: number;
  nombre: string;
  slug: string;
  descripcion_web: string | null;
  imagen_url: string | null;
  orden: number;
}

// --- HeroBanner ---

function HeroBanner() {
  return (
    <section
      className="relative w-full overflow-hidden"
      style={{
        background: "linear-gradient(135deg, #333333 0%, #1a1a1a 50%, #E8751A 100%)",
      }}
    >
      {/* Decorative circles */}
      <div className="absolute top-[-80px] right-[-80px] w-[300px] h-[300px] rounded-full bg-[#E8751A]/10" />
      <div className="absolute bottom-[-60px] left-[-60px] w-[200px] h-[200px] rounded-full bg-white/5" />

      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 flex flex-col items-center text-center gap-6">
        {/* Logo text */}
        <span className="text-white/80 text-sm font-semibold tracking-[0.2em] uppercase">
          Grupo Pedregal
        </span>

        <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight max-w-3xl">
          El Almacén de los Profesionales del Yeso
        </h1>

        <p className="text-white/70 text-base sm:text-lg max-w-2xl leading-relaxed">
          Yesos, escayolas, aislamientos, placas PYL y preformados. Todo lo que
          necesitas para tu obra, con precios competitivos y servicio profesional.
        </p>

        <a
          href="/productos"
          className="mt-2 inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-bold text-sm transition-colors hover:opacity-90"
          style={{ backgroundColor: "#E8751A" }}
        >
          Explorar productos
          <ArrowRight size={18} />
        </a>
      </div>
    </section>
  );
}

// --- CategoryGrid ---

function CategoryCard({ category }: { category: Category }) {
  const router = useRouter();

  return (
    <button
      onClick={() => router.push(`/productos?category=${category.slug}`)}
      className="group flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md hover:border-[#E8751A]/50 transition-all duration-200 text-left cursor-pointer"
    >
      {/* Image */}
      <div className="relative w-full aspect-[16/10] bg-[#F5F5F5] overflow-hidden">
        {category.imagen_url ? (
          <img
            src={category.imagen_url}
            alt={category.nombre}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <Package size={48} className="text-gray-300" />
          </div>
        )}
      </div>

      {/* Content */}
      <div className="p-4 flex flex-col gap-1.5 flex-1">
        <h3 className="text-[#333333] font-semibold text-base group-hover:text-[#E8751A] transition-colors">
          {category.nombre}
        </h3>
        {category.descripcion_web && (
          <p className="text-gray-500 text-sm line-clamp-2 leading-relaxed">
            {category.descripcion_web}
          </p>
        )}
        <span className="mt-auto pt-2 text-[#E8751A] text-sm font-medium inline-flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          Ver productos <ArrowRight size={14} />
        </span>
      </div>
    </button>
  );
}

function CategoryGrid({ categories, loading, error }: {
  categories: Category[];
  loading: boolean;
  error: string | null;
}) {
  if (loading) {
    return (
      <section className="py-12 sm:py-16 bg-[#F5F5F5]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-2xl sm:text-3xl font-bold text-[#333333] text-center mb-10">
            Nuestras Categorías
          </h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="bg-white rounded-xl h-64 animate-pulse" />
            ))}
          </div>
        </div>
      </section>
    );
  }

  if (error) {
    return (
      <section className="py-12 sm:py-16 bg-[#F5F5F5]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <p className="text-gray-500">{error}</p>
        </div>
      </section>
    );
  }

  if (categories.length === 0) {
    return null;
  }

  return (
    <section className="py-12 sm:py-16 bg-[#F5F5F5]">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-2xl sm:text-3xl font-bold text-[#333333] text-center mb-3">
          Nuestras Categorías
        </h2>
        <p className="text-gray-500 text-center mb-10 max-w-xl mx-auto">
          Explora nuestra amplia gama de productos organizados por categoría
        </p>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {categories.map((cat) => (
            <CategoryCard key={cat.id} category={cat} />
          ))}
        </div>
      </div>
    </section>
  );
}

// --- PortalDescription ---

const capabilities = [
  {
    icon: Search,
    title: "Explorar productos",
    description:
      "Navega por nuestro catálogo completo con filtros por categoría, marca y proveedor para encontrar exactamente lo que necesitas.",
    href: "/productos",
  },
  {
    icon: Package,
    title: "Consultar precios",
    description:
      "Consulta precios PVP actualizados de todos nuestros productos. Clientes registrados acceden a precios personalizados por tarifa.",
    href: "/productos",
  },
  {
    icon: Calculator,
    title: "Calcular materiales",
    description:
      "Introduce los m² de tu obra y calcula automáticamente las cantidades de materiales necesarios con porcentaje de merma configurable.",
    href: "/productos",
  },
  {
    icon: FileText,
    title: "Generar presupuestos",
    description:
      "Genera presupuestos detallados en PDF con desglose de materiales, cantidades, precios y totales. Envíalos por email directamente.",
    href: "/productos",
  },
];

function PortalDescription() {
  return (
    <section className="py-12 sm:py-16 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-2xl sm:text-3xl font-bold text-[#333333] text-center mb-3">
          Tu portal profesional de materiales
        </h2>
        <p className="text-gray-500 text-center mb-10 max-w-2xl mx-auto">
          Todas las herramientas que necesitas para gestionar tus proyectos de
          construcción en un solo lugar
        </p>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {capabilities.map((cap) => (
            <Link
              key={cap.title}
              href={cap.href}
              className="group flex flex-col items-center text-center p-6 rounded-xl bg-[#F5F5F5] hover:shadow-md hover:bg-white hover:border-[#E8751A]/30 border border-transparent transition-all duration-200 cursor-pointer"
            >
              <div
                className="w-12 h-12 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"
                style={{ backgroundColor: "#E8751A" }}
              >
                <cap.icon size={24} className="text-white" />
              </div>
              <h3 className="text-[#333333] font-semibold text-base mb-2 group-hover:text-[#E8751A] transition-colors">
                {cap.title}
              </h3>
              <p className="text-gray-500 text-sm leading-relaxed">
                {cap.description}
              </p>
              <span className="mt-3 text-[#E8751A] text-sm font-medium inline-flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                Ir <ArrowRight size={14} />
              </span>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}

// --- Main Landing Page ---

export default function LandingPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const res = await api.get("/api/categories/public");
        const data = Array.isArray(res.data) ? res.data : res.data.data ?? [];
        setCategories(data);
      } catch {
        setError("No se pudieron cargar las categorías. Inténtalo de nuevo.");
      } finally {
        setLoading(false);
      }
    };
    fetchCategories();
  }, []);

  return (
    <>
      <HeroBanner />
      <CategoryGrid categories={categories} loading={loading} error={error} />
      <PortalDescription />
    </>
  );
}
