"use client";

import { manrope } from "@/app/fonts";
import { useUser } from "@/context/UserContext";
import Link from "next/link";
import { FileText, Users, Settings } from "lucide-react";

export default function HomePage() {
  const { user, loading } = useUser();

  if (loading) {
    return (
      <div className="flex min-h-[calc(100vh-80px)] items-center justify-center">
        <div className="w-8 h-8 border-2 border-white/30 border-t-white rounded-full animate-spin" />
      </div>
    );
  }

  const menuItems = [
    {
      title: "Tarifas",
      description: "Consulta y exporta tarifas de productos",
      icon: FileText,
      href: "/app/tarifas",
      color: "from-blue-500 to-indigo-600",
    },
    {
      title: "Clientes",
      description: "Gestiona tus clientes",
      icon: Users,
      href: "/app/clientes",
      color: "from-green-500 to-emerald-600",
    },
    {
      title: "Configuración",
      description: "Ajustes del sistema",
      icon: Settings,
      href: "/app/configuracion",
      color: "from-purple-500 to-pink-600",
    },
  ];

  return (
    <div className="max-w-7xl m-auto px-5 lg:px-20 py-12">
      <div className="flex flex-col gap-6">
        <div>
          <h1 className={`text-[32px] font-semibold text-white ${manrope.className}`}>
            Bienvenido{user ? `, ${user.name}` : ""}
          </h1>
          <p className="text-[#BBB8AA] text-[16px] mt-2">
            Sistema de gestión de tarifas y clientes
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-4">
          {menuItems.map((item) => {
            const Icon = item.icon;
            return (
              <Link
                key={item.title}
                href={item.href}
                className="group flex flex-col gap-4 p-6 rounded-xl bg-white/[0.06] border border-white/10 hover:bg-white/[0.09] transition-all duration-200 cursor-pointer hover:scale-105"
              >
                <div className={`w-12 h-12 rounded-lg bg-gradient-to-br ${item.color} flex items-center justify-center`}>
                  <Icon className="text-white" size={24} />
                </div>
                <div>
                  <h2 className={`text-[18px] font-semibold text-white ${manrope.className} group-hover:text-yellow-300 transition-colors`}>
                    {item.title}
                  </h2>
                  <p className="text-[#BBB8AA] text-[14px] mt-1">{item.description}</p>
                </div>
              </Link>
            );
          })}
        </div>

        {user?.role && (
          <div className="mt-8 p-6 rounded-xl bg-white/[0.06] border border-white/10">
            <h3 className={`text-[16px] font-semibold text-white ${manrope.className} mb-2`}>
              Tu Rol
            </h3>
            <p className="text-[#BBB8AA] text-[14px]">
              Estás conectado como <span className="text-yellow-300 font-medium">{user.role.name}</span>
            </p>
          </div>
        )}
      </div>
    </div>
  );
}

