import Link from "next/link";
import { manrope } from "../fonts";

export default function LandingPage() {
  return (
    <div className="flex flex-col min-h-screen items-center justify-center bg-tertiary px-5 gap-10">
      <div className="flex flex-col items-center gap-6 text-center">
        <h1 className={`text-[48px] font-semibold text-white leading-tight ${manrope.className}`}>
          Bienvenido a Tony App
        </h1>
        <p className="text-[#BBB8AA] text-[16px] max-w-md leading-relaxed">
          Plataforma de gestión empresarial. Inicia sesión para acceder a tu panel.
        </p>
        <div className="flex gap-3 mt-2">
          <Link
            href="/login"
            className="flex items-center justify-center rounded-[10px] px-6 py-2.5 bg-primary font-bold text-white hover:opacity-90 transition-opacity"
          >
            Iniciar sesión
          </Link>
        </div>
      </div>
    </div>
  );
}
