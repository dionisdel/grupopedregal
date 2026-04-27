"use client";

import { useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { Loader2, AlertCircle, Eye, EyeOff } from "lucide-react";
import { authService } from "@/services/auth.service";
import { useUser } from "@/context/UserContext";
import { useCart } from "@/context/CartContext";

export default function LoginClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const redirectTo = searchParams.get("redirect") || "/";
  const { fetchUser } = useUser();
  const { mergeCartOnLogin } = useCart();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const trimmedEmail = email.trim();
    if (!trimmedEmail || !password) {
      setError("Introduce tu email y contraseña.");
      return;
    }

    setLoading(true);
    try {
      const response = await authService.login({ email: trimmedEmail, password });
      document.cookie = `token=${response.token}; path=/; max-age=86400; SameSite=Lax`;
      await fetchUser();
      await mergeCartOnLogin();
      router.push(redirectTo);
    } catch (err: unknown) {
      const axiosErr = err as { response?: { status?: number; data?: { message?: string } } };
      if (axiosErr.response?.status === 403) {
        setError("Tu cuenta está pendiente de aprobación.");
      } else {
        setError(axiosErr.response?.data?.message || "Credenciales incorrectas.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center bg-[#F5F5F5] px-4">
      <div className="max-w-md w-full bg-white rounded-xl shadow-lg p-6 sm:p-8">
        <h1 className="text-xl font-bold text-[#333] mb-1">Iniciar sesión</h1>
        <p className="text-sm text-gray-500 mb-6">
          Introduce tus credenciales para acceder al área de cliente.
        </p>

        <form onSubmit={handleSubmit} noValidate className="space-y-4">
          {error && (
            <div className="flex items-center gap-2 p-3 rounded-lg bg-red-50 text-red-700">
              <AlertCircle size={16} className="shrink-0" />
              <span className="text-sm">{error}</span>
            </div>
          )}

          <div>
            <label htmlFor="login-email" className="block text-sm font-medium text-gray-600 mb-1">
              Email
            </label>
            <input
              id="login-email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => { setEmail(e.target.value); if (error) setError(null); }}
              disabled={loading}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-[#333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50"
              placeholder="tu@email.com"
            />
          </div>

          <div>
            <label htmlFor="login-password" className="block text-sm font-medium text-gray-600 mb-1">
              Contraseña
            </label>
            <div className="relative">
              <input
                id="login-password"
                type={showPassword ? "text" : "password"}
                autoComplete="current-password"
                value={password}
                onChange={(e) => { setPassword(e.target.value); if (error) setError(null); }}
                disabled={loading}
                className="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg text-sm text-[#333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50"
                placeholder="••••••••"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                aria-label={showPassword ? "Ocultar contraseña" : "Mostrar contraseña"}
                tabIndex={-1}
              >
                {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-bold text-white bg-[#E8751A] hover:opacity-90 transition-colors disabled:opacity-60"
          >
            {loading ? (
              <><Loader2 size={16} className="animate-spin" /> Entrando…</>
            ) : (
              "ENTRAR"
            )}
          </button>

          <p className="text-center text-sm text-gray-500">
            ¿No tienes cuenta?{" "}
            <Link href="/registro" className="text-[#E8751A] font-medium hover:underline">
              Regístrate
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
