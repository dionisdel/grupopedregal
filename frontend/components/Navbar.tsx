"use client";

import { useUser } from "@/context/UserContext";
import { LogOut, X } from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState } from "react";
import api from "@/services/axios-instance";

const Navbar = () => {
  const [open, setOpen] = useState(false);
  const [avatarOpen, setAvatarOpen] = useState(false);
  const path = usePathname();
  const router = useRouter();
  const { user, clearUser, loading } = useUser();

  const isActive = (href: string) => path.includes(href);

  const handleLogout = async () => {
    try { await api.post("/logout"); } catch { /* ignore */ }
    clearUser();
    router.push("/login");
  };

  return (
    <>
      <div className="relative w-full">
        <div className="pointer-events-none absolute inset-0 bg-white/20 backdrop-blur-[60px] border-b border-white/25" />
        <div className="pointer-events-none absolute inset-0 bg-linear-to-r from-black/85 via-black/50 to-black/85" />
        <div className="relative z-10 max-w-7xl w-full m-auto flex items-center justify-between py-5 lg:px-20 px-5">
          <Link href="/">
            <span className="text-white font-bold text-xl tracking-tight">Tony App</span>
          </Link>

          {/* Desktop nav */}
          <div className="lg:flex hidden gap-8 text-[15px] text-white/90">
            <Link href={user ? "/app/home" : "/"} className={isActive("/app") ? "text-yellow-300" : ""}>
              Inicio
            </Link>
          </div>

          <div className="lg:flex hidden gap-2.5" suppressHydrationWarning>
            {loading ? (
              <div className="w-10 h-10 rounded-full bg-white/20 animate-pulse" />
            ) : user ? (
              <div className="relative">
                <button
                  onClick={() => setAvatarOpen(!avatarOpen)}
                  className="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm"
                >
                  {user.name.charAt(0).toUpperCase()}
                </button>
                {avatarOpen && (
                  <>
                    <div className="fixed inset-0 z-10" onClick={() => setAvatarOpen(false)} />
                    <div className="absolute right-0 top-12 w-48 bg-[#0B0B0C] border border-white/10 rounded-lg shadow-xl z-20 py-1 overflow-hidden">
                      <div className="px-4 py-2 border-b border-white/10">
                        <p className="text-sm text-white font-medium truncate">{user.name}</p>
                        <p className="text-xs text-gray-400 truncate">{user.email}</p>
                      </div>
                      <button
                        onClick={handleLogout}
                        className="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-white/5 flex items-center gap-2 transition-colors"
                      >
                        <LogOut size={16} />
                        Cerrar sesión
                      </button>
                    </div>
                  </>
                )}
              </div>
            ) : (
              <Link className="bg-primary rounded-[10px] h-10 px-4 flex items-center font-bold" href="/login">
                Iniciar sesión
              </Link>
            )}
          </div>

          {/* Mobile hamburger */}
          <button className="lg:hidden flex cursor-pointer items-center justify-center rounded-full p-2" onClick={() => setOpen(true)}>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
              <path d="M7 12.5H25.6667" stroke="white" strokeWidth="2.5" strokeLinecap="round" />
              <path d="M7 19.5H25.6667" stroke="white" strokeWidth="2.5" strokeLinecap="round" />
            </svg>
          </button>
        </div>
      </div>

      {/* Mobile overlay */}
      <div
        onClick={() => setOpen(false)}
        className={`fixed inset-0 bg-black/50 backdrop-blur-sm z-40 transition-opacity duration-300 ${open ? "opacity-100 pointer-events-auto" : "opacity-0 pointer-events-none"}`}
      />

      {/* Mobile drawer */}
      <aside className={`fixed top-0 right-0 h-full w-[80%] max-w-sm bg-[#0B0B0C] z-50 transform transition-transform duration-300 ease-out ${open ? "translate-x-0" : "translate-x-full"}`}>
        <div className="flex items-center justify-between px-6 py-5 border-b border-white/10">
          <span className="text-white font-bold text-lg">Tony App</span>
          <button onClick={() => setOpen(false)}><X className="text-white" /></button>
        </div>
        <nav className="flex flex-col gap-6 px-6 py-8 text-white text-[16px]">
          <Link onClick={() => setOpen(false)} href={user ? "/app/home" : "/"}>Inicio</Link>
          <div className="pt-6 border-t border-white/10 flex flex-col gap-4" suppressHydrationWarning>
            {user ? (
              <button onClick={handleLogout} className="bg-primary rounded-lg py-3 text-center font-bold text-white w-full">
                Cerrar sesión
              </button>
            ) : (
              <Link onClick={() => setOpen(false)} href="/login" className="bg-primary rounded-lg py-3 text-center font-bold text-white">
                Iniciar sesión
              </Link>
            )}
          </div>
        </nav>
      </aside>
    </>
  );
};

export default Navbar;
