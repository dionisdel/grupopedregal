"use client";

import { useUser } from "@/context/UserContext";
import { useCart } from "@/context/CartContext";
import { Menu, X, LogOut, ChevronRight, ShoppingCart, User } from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState, useEffect } from "react";
import api from "@/services/axios-instance";

interface NavLink {
  label: string;
  href: string;
}

const CorporateNavbar = () => {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const pathname = usePathname();
  const router = useRouter();
  const { user, clearUser, loading } = useUser();
  const { itemCount: cartCount } = useCart();

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
    return () => { document.body.style.overflow = ""; };
  }, [drawerOpen]);

  const roleSlug = user?.role?.slug;
  const isAdmin = roleSlug === "admin" || roleSlug === "superadmin";

  const navLinks: NavLink[] = [
    { label: "Productos", href: "/categorias" },
    { label: "Nuestras marcas", href: "/contacto" },
    { label: "Contacto", href: "/contacto" },
  ];

  const isActive = (href: string) => {
    if (href === "/") return pathname === "/";
    return pathname.startsWith(href);
  };

  const handleLogout = async () => {
    try { await api.post("/api/logout"); } catch { /* ignore */ }
    clearUser();
    router.push("/");
  };

  const handleCartClick = () => {
    if (!user) {
      router.push("/login");
    } else {
      router.push("/carrito");
    }
  };

  return (
    <>
      <nav
        className="w-full sticky top-0 z-30"
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
              <a
                key={link.href + link.label}
                href={link.href}
                className={`text-sm font-medium transition-colors ${
                  isActive(link.href)
                    ? "text-[#E8751A]"
                    : "text-white hover:text-[#E8751A]"
                }`}
              >
                {link.label}
              </a>
            ))}
            {isAdmin && (
              <>
                <a
                  href="/admin/productos/"
                  className="text-sm font-medium text-white hover:text-[#E8751A] transition-colors"
                >
                  Editar productos
                </a>
                <a
                  href={`${process.env.NEXT_PUBLIC_API_URL || ''}/admin`}
                  className="text-sm font-medium text-white hover:text-[#E8751A] transition-colors"
                >
                  Panel Admin
                </a>
              </>
            )}
          </div>

          {/* Desktop right section */}
          <div className="hidden lg:flex items-center gap-4" suppressHydrationWarning>
            {/* Cart icon */}
            <button
              onClick={handleCartClick}
              className="relative text-white hover:text-[#E8751A] transition-colors"
              aria-label="Carrito de compra"
            >
              <ShoppingCart size={22} />
              {cartCount > 0 && (
                <span className="absolute -top-2 -right-2 bg-[#E8751A] text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center">
                  {cartCount > 99 ? "99+" : cartCount}
                </span>
              )}
            </button>

            {loading ? (
              <div className="w-8 h-8 rounded-full bg-white/20 animate-pulse" />
            ) : user ? (
              <div className="flex items-center gap-3">
                <Link
                  href="/app/perfil"
                  className="flex items-center gap-1.5 text-sm text-white hover:text-[#E8751A] transition-colors"
                >
                  <User size={16} />
                  <span>Mi cuenta</span>
                </Link>
                <button
                  onClick={handleLogout}
                  className="flex items-center gap-1.5 text-sm text-white/80 hover:text-white transition-colors"
                  aria-label="Cerrar sesión"
                >
                  <LogOut size={16} />
                </button>
              </div>
            ) : (
              <a
                href="/login/"
                className="text-sm font-bold text-white px-4 py-2 rounded transition-colors hover:opacity-90"
                style={{ backgroundColor: "#E8751A" }}
              >
                Login
              </a>
            )}
          </div>

          {/* Mobile right: cart + hamburger */}
          <div className="lg:hidden flex items-center gap-3">
            <button
              onClick={handleCartClick}
              className="relative text-white hover:text-[#E8751A] transition-colors"
              aria-label="Carrito de compra"
            >
              <ShoppingCart size={22} />
              {cartCount > 0 && (
                <span className="absolute -top-2 -right-2 bg-[#E8751A] text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center">
                  {cartCount > 99 ? "99+" : cartCount}
                </span>
              )}
            </button>
            <button
              className="flex items-center justify-center p-2 text-white"
              onClick={() => setDrawerOpen(true)}
              aria-label="Abrir menú"
            >
              <Menu size={28} />
            </button>
          </div>
        </div>
      </nav>

      {/* Mobile overlay */}
      <div
        onClick={() => setDrawerOpen(false)}
        className={`fixed inset-0 bg-black/50 z-40 transition-opacity duration-300 ${
          drawerOpen ? "opacity-100 pointer-events-auto" : "opacity-0 pointer-events-none"
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
        <div className="flex items-center justify-between px-5 py-4 border-b border-white/10">
          <span className="text-white font-bold text-lg">Grupo Pedregal</span>
          <button onClick={() => setDrawerOpen(false)} aria-label="Cerrar menú">
            <X className="text-white" size={24} />
          </button>
        </div>

        <nav className="flex flex-col px-5 py-6 gap-1">
          <Link
            href="/"
            onClick={() => setDrawerOpen(false)}
            className={`flex items-center justify-between py-3 px-3 rounded-lg text-[15px] font-medium transition-colors ${
              pathname === "/" ? "text-[#E8751A] bg-white/5" : "text-white hover:bg-white/5"
            }`}
          >
            Inicio
            {pathname === "/" && <ChevronRight size={16} className="text-[#E8751A]" />}
          </Link>

          {navLinks.map((link) => (
            <Link
              key={link.href + link.label}
              href={link.href}
              onClick={() => setDrawerOpen(false)}
              className={`flex items-center justify-between py-3 px-3 rounded-lg text-[15px] font-medium transition-colors ${
                isActive(link.href) ? "text-[#E8751A] bg-white/5" : "text-white hover:bg-white/5"
              }`}
            >
              {link.label}
              {isActive(link.href) && <ChevronRight size={16} className="text-[#E8751A]" />}
            </Link>
          ))}

          {isAdmin && (
            <>
              <Link
                href="/admin/productos"
                onClick={() => setDrawerOpen(false)}
                className="flex items-center justify-between py-3 px-3 rounded-lg text-[15px] font-medium text-white hover:bg-white/5 transition-colors"
              >
                Editar productos
              </Link>
              <a
                href={`${process.env.NEXT_PUBLIC_API_URL || ''}/admin`}
                onClick={() => setDrawerOpen(false)}
                className="flex items-center justify-between py-3 px-3 rounded-lg text-[15px] font-medium text-white hover:bg-white/5 transition-colors"
              >
                Panel Admin
              </a>
            </>
          )}

          <div className="mt-6 pt-6 border-t border-white/10 flex flex-col gap-3" suppressHydrationWarning>
            {user ? (
              <>
                <div className="px-3 py-2">
                  <p className="text-white text-sm font-medium">{user.name}</p>
                  <p className="text-white/60 text-xs">{user.email}</p>
                </div>
                <button
                  onClick={() => { setDrawerOpen(false); handleLogout(); }}
                  className="flex items-center gap-2 py-3 px-3 rounded-lg text-sm font-medium text-red-400 hover:bg-white/5 transition-colors"
                >
                  <LogOut size={16} />
                  Cerrar sesión
                </button>
              </>
            ) : (
              <a
                href="/login/"
                onClick={() => setDrawerOpen(false)}
                className="text-center py-3 rounded-lg text-sm font-bold text-white transition-colors"
                style={{ backgroundColor: "#E8751A" }}
              >
                Login
              </a>
            )}
          </div>
        </nav>
      </aside>
    </>
  );
};

export default CorporateNavbar;
