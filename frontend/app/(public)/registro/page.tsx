"use client";

import { useState } from "react";
import Link from "next/link";
import { Loader2, AlertCircle, Eye, EyeOff, CheckCircle2 } from "lucide-react";
import api from "@/services/axios-instance";

interface FieldErrors {
  nombre?: string[];
  email?: string[];
  telefono?: string[];
  empresa?: string[];
  nif_cif?: string[];
  password?: string[];
}

const NIF_CIF_REGEX = /^[A-Za-z0-9]\d{7}[A-Za-z0-9]$/;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default function RegistroPage() {
  const [form, setForm] = useState({
    nombre: "",
    email: "",
    telefono: "",
    empresa: "",
    nif_cif: "",
    password: "",
  });
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const handleChange = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    // Clear field error on change
    if (fieldErrors[field]) {
      setFieldErrors((prev) => {
        const next = { ...prev };
        delete next[field];
        return next;
      });
    }
    if (generalError) setGeneralError(null);
  };

  const validateClient = (): boolean => {
    const errors: FieldErrors = {};

    if (!form.nombre.trim()) {
      errors.nombre = ["El nombre es obligatorio."];
    }
    if (!form.email.trim()) {
      errors.email = ["El email es obligatorio."];
    } else if (!EMAIL_REGEX.test(form.email.trim())) {
      errors.email = ["El formato del email no es válido."];
    }
    if (!form.telefono.trim()) {
      errors.telefono = ["El teléfono es obligatorio."];
    }
    if (!form.empresa.trim()) {
      errors.empresa = ["La empresa/razón social es obligatoria."];
    }
    if (!form.nif_cif.trim()) {
      errors.nif_cif = ["El NIF/CIF es obligatorio."];
    } else if (!NIF_CIF_REGEX.test(form.nif_cif.trim())) {
      errors.nif_cif = ["El formato del NIF/CIF no es válido (ej: B12345678)."];
    }
    if (!form.password) {
      errors.password = ["La contraseña es obligatoria."];
    } else if (form.password.length < 8) {
      errors.password = ["La contraseña debe tener al menos 8 caracteres."];
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setGeneralError(null);

    if (!validateClient()) return;

    setLoading(true);
    try {
      await api.post("/api/register", {
        nombre: form.nombre.trim(),
        email: form.email.trim(),
        telefono: form.telefono.trim(),
        empresa: form.empresa.trim(),
        nif_cif: form.nif_cif.trim(),
        password: form.password,
      });
      setSuccess(true);
    } catch (err: unknown) {
      const axiosErr = err as {
        response?: { status?: number; data?: { errors?: FieldErrors; message?: string } };
      };
      if (axiosErr.response?.status === 422 && axiosErr.response.data?.errors) {
        setFieldErrors(axiosErr.response.data.errors);
      } else if (axiosErr.response?.status === 429) {
        setGeneralError("Demasiadas solicitudes. Inténtalo más tarde.");
      } else {
        setGeneralError(
          axiosErr.response?.data?.message ||
            "No se pudo completar el registro. Inténtalo de nuevo."
        );
      }
    } finally {
      setLoading(false);
    }
  };

  // Success state
  if (success) {
    return (
      <div className="min-h-[80vh] flex items-center justify-center bg-[#F5F5F5] px-4">
        <div className="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center">
          <div className="flex justify-center mb-4">
            <CheckCircle2 size={48} className="text-green-500" />
          </div>
          <h2 className="text-xl font-bold text-[#333333] mb-2">
            ¡Registro completado!
          </h2>
          <p className="text-sm text-gray-600 mb-6">
            Tu cuenta ha sido creada. Está pendiente de aprobación por un
            administrador. Te notificaremos por email cuando esté activa.
          </p>
          <Link
            href="/"
            className="inline-block px-6 py-3 rounded-lg text-sm font-bold text-white bg-[#E8751A] hover:opacity-90 transition-colors"
          >
            Volver al inicio
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-[80vh] flex items-center justify-center bg-[#F5F5F5] px-4 py-10">
      <div className="max-w-lg w-full bg-white rounded-xl shadow-lg p-6 sm:p-8">
        <h1 className="text-xl font-bold text-[#333333] mb-1">
          Crear cuenta de cliente
        </h1>
        <p className="text-sm text-gray-500 mb-6">
          Completa el formulario para solicitar acceso al área de cliente.
        </p>

        <form onSubmit={handleSubmit} noValidate className="space-y-4">
          {/* General error */}
          {generalError && (
            <div className="flex items-center gap-2 p-3 rounded-lg bg-red-50 text-red-700">
              <AlertCircle size={16} className="shrink-0" />
              <span className="text-sm">{generalError}</span>
            </div>
          )}

          {/* Nombre */}
          <div>
            <label
              htmlFor="reg-nombre"
              className="block text-sm font-medium text-gray-600 mb-1"
            >
              Nombre completo <span className="text-red-500">*</span>
            </label>
            <input
              id="reg-nombre"
              type="text"
              autoComplete="name"
              value={form.nombre}
              onChange={(e) => handleChange("nombre", e.target.value)}
              disabled={loading}
              className={`w-full px-3 py-2 border rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50 ${
                fieldErrors.nombre ? "border-red-400" : "border-gray-300"
              }`}
              placeholder="Tu nombre completo"
            />
            {fieldErrors.nombre && (
              <p className="mt-1 text-xs text-red-600">{fieldErrors.nombre[0]}</p>
            )}
          </div>

          {/* Email */}
          <div>
            <label
              htmlFor="reg-email"
              className="block text-sm font-medium text-gray-600 mb-1"
            >
              Email <span className="text-red-500">*</span>
            </label>
            <input
              id="reg-email"
              type="email"
              autoComplete="email"
              value={form.email}
              onChange={(e) => handleChange("email", e.target.value)}
              disabled={loading}
              className={`w-full px-3 py-2 border rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50 ${
                fieldErrors.email ? "border-red-400" : "border-gray-300"
              }`}
              placeholder="tu@email.com"
            />
            {fieldErrors.email && (
              <p className="mt-1 text-xs text-red-600">{fieldErrors.email[0]}</p>
            )}
          </div>

          {/* Teléfono */}
          <div>
            <label
              htmlFor="reg-telefono"
              className="block text-sm font-medium text-gray-600 mb-1"
            >
              Teléfono <span className="text-red-500">*</span>
            </label>
            <input
              id="reg-telefono"
              type="tel"
              autoComplete="tel"
              value={form.telefono}
              onChange={(e) => handleChange("telefono", e.target.value)}
              disabled={loading}
              className={`w-full px-3 py-2 border rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50 ${
                fieldErrors.telefono ? "border-red-400" : "border-gray-300"
              }`}
              placeholder="+34 600 000 000"
            />
            {fieldErrors.telefono && (
              <p className="mt-1 text-xs text-red-600">
                {fieldErrors.telefono[0]}
              </p>
            )}
          </div>

          {/* Empresa / Razón Social */}
          <div>
            <label
              htmlFor="reg-empresa"
              className="block text-sm font-medium text-gray-600 mb-1"
            >
              Empresa / Razón Social <span className="text-red-500">*</span>
            </label>
            <input
              id="reg-empresa"
              type="text"
              autoComplete="organization"
              value={form.empresa}
              onChange={(e) => handleChange("empresa", e.target.value)}
              disabled={loading}
              className={`w-full px-3 py-2 border rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50 ${
                fieldErrors.empresa ? "border-red-400" : "border-gray-300"
              }`}
              placeholder="Nombre de tu empresa"
            />
            {fieldErrors.empresa && (
              <p className="mt-1 text-xs text-red-600">
                {fieldErrors.empresa[0]}
              </p>
            )}
          </div>

          {/* NIF/CIF */}
          <div>
            <label
              htmlFor="reg-nif"
              className="block text-sm font-medium text-gray-600 mb-1"
            >
              NIF/CIF <span className="text-red-500">*</span>
            </label>
            <input
              id="reg-nif"
              type="text"
              value={form.nif_cif}
              onChange={(e) => handleChange("nif_cif", e.target.value)}
              disabled={loading}
              className={`w-full px-3 py-2 border rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50 ${
                fieldErrors.nif_cif ? "border-red-400" : "border-gray-300"
              }`}
              placeholder="B12345678"
            />
            {fieldErrors.nif_cif && (
              <p className="mt-1 text-xs text-red-600">
                {fieldErrors.nif_cif[0]}
              </p>
            )}
          </div>

          {/* Contraseña */}
          <div>
            <label
              htmlFor="reg-password"
              className="block text-sm font-medium text-gray-600 mb-1"
            >
              Contraseña <span className="text-red-500">*</span>
            </label>
            <div className="relative">
              <input
                id="reg-password"
                type={showPassword ? "text" : "password"}
                autoComplete="new-password"
                value={form.password}
                onChange={(e) => handleChange("password", e.target.value)}
                disabled={loading}
                className={`w-full px-3 py-2 pr-10 border rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50 ${
                  fieldErrors.password ? "border-red-400" : "border-gray-300"
                }`}
                placeholder="Mínimo 8 caracteres"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                aria-label={
                  showPassword ? "Ocultar contraseña" : "Mostrar contraseña"
                }
                tabIndex={-1}
              >
                {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
            {fieldErrors.password && (
              <p className="mt-1 text-xs text-red-600">
                {fieldErrors.password[0]}
              </p>
            )}
          </div>

          {/* Submit */}
          <button
            type="submit"
            disabled={loading}
            className="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-bold text-white bg-[#E8751A] hover:opacity-90 transition-colors disabled:opacity-60"
          >
            {loading ? (
              <>
                <Loader2 size={16} className="animate-spin" />
                Registrando…
              </>
            ) : (
              "CREAR CUENTA"
            )}
          </button>

          {/* Link to login */}
          <p className="text-center text-sm text-gray-500">
            ¿Ya tienes cuenta?{" "}
            <Link
              href="/"
              className="text-[#E8751A] font-medium hover:underline"
            >
              Inicia sesión
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
