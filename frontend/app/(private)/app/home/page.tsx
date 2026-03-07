"use client";

import { manrope } from "@/app/fonts";
import { useUser } from "@/context/UserContext";

export default function HomePage() {
  const { user, loading } = useUser();

  if (loading) {
    return (
      <div className="flex min-h-[calc(100vh-80px)] items-center justify-center">
        <div className="w-8 h-8 border-2 border-white/30 border-t-white rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="max-w-7xl m-auto px-5 lg:px-20 py-12">
      <div className="flex flex-col gap-6">
        <h1 className={`text-[32px] font-semibold text-white ${manrope.className}`}>
          Bienvenido{user ? `, ${user.name}` : ""}
        </h1>
        <p className="text-[#BBB8AA] text-[16px]">
          Has iniciado sesión correctamente. Desde aquí gestionarás tu aplicación.
        </p>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-4">
          {["Panel de control", "Usuarios", "Configuración"].map((item) => (
            <div
              key={item}
              className="flex flex-col gap-3 p-6 rounded-xl bg-white/[0.06] border border-white/10 hover:bg-white/[0.09] transition-colors cursor-pointer"
            >
              <h2 className={`text-[18px] font-semibold text-white ${manrope.className}`}>{item}</h2>
              <p className="text-[#BBB8AA] text-[14px]">Accede a {item.toLowerCase()}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
