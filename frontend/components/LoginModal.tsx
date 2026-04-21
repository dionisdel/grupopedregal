"use client";

import { useState } from "react";
import { X, Loader2, AlertCircle, Eye, EyeOff } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { authService } from "@/services/auth.service";
import { useUser } from "@/context/UserContext";

interface LoginModalProps {
  open: boolean;
  onClose: () => void;
}

export default function LoginModal({ open, onClose }: LoginModalProps) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const router = useRouter();
  const { fetchUser } = useUser();

  if (!open) return null;

  const handleClose = () => {
    setEmail("");
    setPassword("");
    setRemember(false);
    setError(null);
    setLoading(false);
    setShowPassword(false);
    onClose();
  };

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
      const response = await authService.login({
        email: trimmedEmail,
        password,
      });

      // Store token in cookie for middleware
      const maxAge = remember ? 2592000 : 86400; // 30 days or 1 day
      document.cookie = `token=${response.token}; path=/; max-age=${maxAge}; SameSite=Lax`;

      // Refresh user context
      await fetchUser();

      // Close modal and redirect
      handleClose();
      router.push("/app/productos");
    } catch (err: unknown) {
      const axiosErr = err as { response?: { status?: number; data?: { message?: string } } };
      if (axiosErr.response?.status === 403) {
        setError("Tu cuenta está pendiente de aprobación.");
      } else {
        setError("Credenciales incorrectas.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      onClick={handleClose}
      role="dialog"
      aria-modal="true"
      aria-label="Iniciar sesión"
    >
      <div
        className="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Close button */}
        <button
          onClick={handleClose}
          className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
          aria-label="Cerrar"
        >
          <X size={20} />
        </button>

        <h2 className="text-lg font-bold text-[#333333] mb-1">
          Área de Cliente
        </h2>
        <p className="text-sm text-gray-500 mb-5">
          Introduce tus credenciales para acceder.
        </p>

        <form onSubmit={handleSubmit} noValidate>
          {/* Email */}
          <label htmlFor="login-email" className="block text-sm font-medium text-gray-600 mb-1">
            Email
          </label>
          <input
            id="login-email"
            type="email"
            placeholder="tu@email.com"
            autoComplete="email"
            value={email}
            onChange={(e) => {
              setEmail(e.target.value);
              if (error) setError(null);
            }}
            disabled={loading}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50"
          />

          {/* Password */}
          <label htmlFor="login-password" className="block text-sm font-medium text-gray-600 mb-1 mt-4">
            Contraseña
          </label>
          <div className="relative">
            <input
              id="login-password"
              type={showPassword ? "text" : "password"}
              placeholder="••••••••"
              autoComplete="current-password"
              value={password}
              onChange={(e) => {
                setPassword(e.target.value);
                if (error) setError(null);
              }}
              disabled={loading}
              className="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50"
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

          {/* Remember + Forgot password */}
          <div className="flex items-center justify-between mt-3">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={remember}
                onChange={(e) => setRemember(e.target.checked)}
                disabled={loading}
                className="w-4 h-4 rounded border-gray-300 text-[#E8751A] focus:ring-[#E8751A]"
              />
              <span className="text-xs text-gray-600">Recordar usuario y contraseña</span>
            </label>
            <Link
              href="/recuperar-password"
              onClick={handleClose}
              className="text-xs text-[#E8751A] hover:underline"
            >
              ¿Has olvidado tu contraseña?
            </Link>
          </div>

          {/* Error message */}
          {error && (
            <div className="flex items-center gap-1.5 mt-3 text-red-600">
              <AlertCircle size={14} />
              <span className="text-sm">{error}</span>
            </div>
          )}

          {/* Submit button */}
          <button
            type="submit"
            disabled={loading}
            className="mt-5 w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-bold text-white bg-[#E8751A] hover:opacity-90 transition-colors disabled:opacity-60"
          >
            {loading ? (
              <>
                <Loader2 size={16} className="animate-spin" />
                Entrando…
              </>
            ) : (
              "ENTRAR"
            )}
          </button>

          {/* Register link */}
          <p className="text-center text-sm text-gray-500 mt-4">
            ¿No tienes cuenta?{" "}
            <Link
              href="/registro"
              onClick={handleClose}
              className="text-[#E8751A] font-medium hover:underline"
            >
              Regístrate como usuario
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
