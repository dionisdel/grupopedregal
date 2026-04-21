"use client";

import { useUser } from "@/context/UserContext";
import { Menu, X, LogOut, ChevronRight } from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState, useEffect } from "react";
import api from "@/services/axios-instance";
import LoginModal from "@/components/LoginModal";

interface NavLink {
  label: string;
  href: string;
}

const CorporateNavbar = () => {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [loginOpen, setLoginOpen] = useState(false);
  const pathname = usePathname();
  const router = useRouter();
  const { user, clearUser, loading } = useUser();

  // Close drawer on route change
  useEffect(() => {
    setDrawerOpen(false);
  }, [pathname]);

  // Prevent body scroll when drawer is open
  useEffect(() => {
    if (drawerOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [drawerOpen]);

  const roleSlug = user?.role?.slug;

  const isAdmin = roleSlug === "admin" || roleSlug === "comercial";
  const isClient = !!user && !isAdmin;

  const getNavLinks = (): NavLink[] => {
    if (isAdmin) {
      return [
        { label: "Inicio", href: "/" },
        { label: "Productos", href: "/productos" },
        { label: "Tarifas", href: "/app/tarifas" },
      ];
    }
    if (isClient) {
      return [
        { label: "Inicio", href: "/" },
        { label: "Productos", href: "/app/productos" },
        { label: "Mis Presupuestos", href: "/app/presupuestos" },
        { label: "Mi Perfil", href: "/app/perfil" },
      ];
    }
    // Public (not authenticated)
    return [
      { label: "Inicio", href: "/" },
      { label: "Productos", href: "/productos" },
      { label: "Contacto", href: "/contacto" },
    ];
  };

  const navLinks = getNavLinks();

  const isActive = (href: string) => {
    if (href === "/") return pathname === "/";
    return pathname.startsWith(href);
  };

  const handleLogout = async () => {
    try {
      await api.post("/api/logout");
    } catch {
      /* ignore */
    }
    clearUser();
    router.push("/");
  };

  const adminPanelUrl =
    (process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000") + "/admin";

  return (
    <>
      {/* Navbar */}
      <nav
        className="w-full"
        style={{ backgroundColor: "#333333" }}
        role="navigation"
        aria-label="Navegación principal"
      >
        <div className="max-w-7xl mx-auto flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
          {/* Logo */}
          <Link href="/" className="flex items-center gap-2 shrink-0">
            <span className="text-white font-bold text-lg tracking-tight">
              Grupo Pedregal
            </span>
          </Link>

          {/* Desktop nav links */}
          <div className="hidden lg:flex items-center gap-6">
            {navLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={`text-sm font-medium transition-colors ${
                  isActive(link.href)
                    ? "text-[#E8751A]"
                    : "text-white hover:text-[#E8751A]"
                }`}
              >
                {link.label}
              </Link>
            ))}
            {isAdmin && (
              <a
                href={adminPanelUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-sm font-medium text-white hover:text-[#E8751A] transition-colors"
              >
                Panel Admin
              </a>
            )}
          </div>

          {/* Desktop right section */}
          <div className="hidden lg:flex items-center gap-3" suppressHydrationWarning>
            {loading ? (
              <div className="w-8 h-8 rounded-full bg-white/20 animate-pulse" />
            ) : user ? (
              <div className="flex items-center gap-3">
                <span className="text-white text-sm">{user.name}</span>
                <button
                  onClick={handleLogout}
                  className="flex items-center gap-1.5 text-sm text-white/80 hover:text-white transition-colors"
                  aria-label="Cerrar sesión"
                >
                  <LogOut size={16} />
                  <span>Salir</span>
                </button>
              </div>
            ) : (
              <button
                onClick={() => setLoginOpen(true)}
                className="text-sm font-bold text-white px-4 py-2 rounded transition-colors"
                style={{ backgroundColor: "#E8751A" }}
              >
                ÁREA DE CLIENTE
              </button>
            )}
          </div>

          {/* Mobile hamburger button */}
          <button
            className="lg:hidden flex items-center justify-center p-2 text-white"
            onClick={() => setDrawerOpen(true)}
            aria-label="Abrir menú"
          >
            <Menu size={28} />
          </button>
        </div>
      </nav>

      {/* Mobile overlay */}
      <div
        onClick={() => setDrawerOpen(false)}
        className={`fixed inset-0 bg-black/50 z-40 transition-opacity duration-300 ${
          drawerOpen
            ? "opacity-100 pointer-events-auto"
            : "opacity-0 pointer-events-none"
        }`}
        aria-hidden="true"
      />

      {/* Mobile drawer */}
      <aside
        className={`fixed top-0 right-0 h-full w-[80%] max-w-sm z-50 transform transition-transform duration-300 ease-out ${
          drawerOpen ? "translate-x-0" : "translate-x-full"
        }`}
        style={{ backgroundColor: "#333333" }}
        role="dialog"
        aria-label="Menú de navegación"
      >
        {/* Drawer header */}
        <div className="flex items-center justify-between px-5 py-4 border-b border-white/10">
          <span className="text-white font-bold text-lg">Grupo Pedregal</span>
          <button
            onClick={() => setDrawerOpen(false)}
            aria-label="Cerrar menú"
          >
            <X className="text-white" size={24} />
          </button>
        </div>

        {/* Drawer nav links */}
        <nav className="flex flex-col px-5 py-6 gap-1">
          {navLinks.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              onClick={() => setDrawerOpen(false)}
              className={`flex items-center justify-between py-3 px-3 rounded-lg text-[15px] font-medium transition-colors ${
                isActive(link.href)
                  ? "text-[#E8751A] bg-white/5"
                  : "text-white hover:bg-white/5"
              }`}
            >
              {link.label}
              {isActive(link.href) && (
                <ChevronRight size={16} className="text-[#E8751A]" />
              )}
            </Link>
          ))}
          {isAdmin && (
            <a
              href={adminPanelUrl}
              target="_blank"
              rel="noopener noreferrer"
              onClick={() => setDrawerOpen(false)}
              className="flex items-center justify-between py-3 px-3 rounded-lg text-[15px] font-medium text-white hover:bg-white/5 transition-colors"
            >
              Panel Admin
            </a>
          )}

          {/* Drawer bottom section */}
          <div
            className="mt-6 pt-6 border-t border-white/10 flex flex-col gap-3"
            suppressHydrationWarning
          >
            {user ? (
              <>
                <div className="px-3 py-2">
                  <p className="text-white text-sm font-medium">{user.name}</p>
                  <p className="text-white/60 text-xs">{user.email}</p>
                </div>
                <button
                  onClick={() => {
                    setDrawerOpen(false);
                    handleLogout();
                  }}
                  className="flex items-center gap-2 py-3 px-3 rounded-lg text-sm font-medium text-red-400 hover:bg-white/5 transition-colors"
                >
                  <LogOut size={16} />
                  Cerrar sesión
                </button>
              </>
            ) : (
              <button
                onClick={() => {
                  setDrawerOpen(false);
                  setLoginOpen(true);
                }}
                className="text-center py-3 rounded-lg text-sm font-bold text-white transition-colors"
                style={{ backgroundColor: "#E8751A" }}
              >
                ÁREA DE CLIENTE
              </button>
            )}
          </div>
        </nav>
      </aside>

      {/* Login Modal */}
      <LoginModal open={loginOpen} onClose={() => setLoginOpen(false)} />
    </>
  );
};

export default CorporateNavbar;
