"use client";

import { useEffect, useState, useCallback, type FormEvent } from "react";
import { User, Save, CheckCircle, AlertCircle, Loader2 } from "lucide-react";
import { useUser } from "@/context/UserContext";
import {
  catalogService,
  type UserProfile,
  type UpdateProfileData,
} from "@/services/catalog.service";

// ─── LoadingSkeleton ───

function LoadingSkeleton() {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-8">
      <div className="flex flex-col gap-5">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="flex flex-col gap-2">
            <div className="h-4 w-24 bg-[#F5F5F5] rounded animate-pulse" />
            <div className="h-10 bg-[#F5F5F5] rounded-lg animate-pulse" />
          </div>
        ))}
      </div>
    </div>
  );
}

// ─── StatusMessage ───

function StatusMessage({
  type,
  message,
}: {
  type: "success" | "error";
  message: string;
}) {
  const isSuccess = type === "success";
  return (
    <div
      className={`flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium ${
        isSuccess
          ? "bg-green-50 text-green-700 border border-green-200"
          : "bg-red-50 text-red-700 border border-red-200"
      }`}
      role="alert"
    >
      {isSuccess ? <CheckCircle size={16} /> : <AlertCircle size={16} />}
      {message}
    </div>
  );
}

// ─── ReadOnlyField ───

function ReadOnlyField({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <label className="block text-sm font-semibold text-[#333333] mb-1.5">
        {label}
      </label>
      <div className="w-full px-4 py-2.5 rounded-lg bg-[#F5F5F5] border border-gray-200 text-gray-500 text-sm cursor-not-allowed">
        {value || "—"}
      </div>
    </div>
  );
}

// ─── EditableField ───

function EditableField({
  label,
  name,
  value,
  onChange,
  type = "text",
  placeholder,
}: {
  label: string;
  name: string;
  value: string;
  onChange: (name: string, value: string) => void;
  type?: string;
  placeholder?: string;
}) {
  return (
    <div>
      <label
        htmlFor={name}
        className="block text-sm font-semibold text-[#333333] mb-1.5"
      >
        {label}
      </label>
      <input
        id={name}
        name={name}
        type={type}
        value={value}
        onChange={(e) => onChange(name, e.target.value)}
        placeholder={placeholder}
        className="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm text-[#333333] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors"
      />
    </div>
  );
}

// ─── UserProfilePage (main) ───

export default function UserProfilePage() {
  const { user } = useUser();

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [status, setStatus] = useState<{
    type: "success" | "error";
    message: string;
  } | null>(null);

  // Editable form state
  const [form, setForm] = useState<UpdateProfileData>({
    name: "",
    telefono: "",
    empresa: "",
  });

  const fetchProfile = useCallback(() => {
    setLoading(true);
    catalogService
      .getProfile()
      .then((data) => {
        setProfile(data);
        setForm({
          name: data.name ?? "",
          telefono: data.telefono ?? "",
          empresa: data.empresa ?? "",
        });
      })
      .catch(() => {
        setStatus({
          type: "error",
          message: "No se pudo cargar el perfil. Inténtalo de nuevo.",
        });
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    fetchProfile();
  }, [fetchProfile]);

  const handleFieldChange = useCallback((name: string, value: string) => {
    setForm((prev) => ({ ...prev, [name]: value }));
    setStatus(null);
  }, []);

  const handleSubmit = useCallback(
    async (e: FormEvent) => {
      e.preventDefault();
      if (!form.name.trim()) {
        setStatus({ type: "error", message: "El nombre es obligatorio." });
        return;
      }
      setSaving(true);
      setStatus(null);
      try {
        await catalogService.updateProfile(form);
        setStatus({
          type: "success",
          message: "Perfil actualizado correctamente.",
        });
      } catch (err: unknown) {
        const msg =
          (err as { response?: { data?: { message?: string } } })?.response
            ?.data?.message ?? "Error al guardar los cambios. Inténtalo de nuevo.";
        setStatus({ type: "error", message: msg });
      } finally {
        setSaving(false);
      }
    },
    [form]
  );

  return (
    <div className="min-h-[60vh] bg-[#F5F5F5]">
      {/* Header */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-[#E8751A]/10 flex items-center justify-center">
              <User size={20} className="text-[#E8751A]" />
            </div>
            <div>
              <h1 className="text-xl sm:text-2xl font-bold text-[#333333]">
                Mi Perfil
              </h1>
              {user && (
                <p className="text-sm text-gray-500">{user.name}</p>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {status && <div className="mb-4"><StatusMessage type={status.type} message={status.message} /></div>}

        {loading ? (
          <LoadingSkeleton />
        ) : profile ? (
          <form onSubmit={handleSubmit}>
            <div className="bg-white rounded-xl border border-gray-200 p-6 sm:p-8">
              <div className="flex flex-col gap-5">
                {/* Editable fields */}
                <EditableField
                  label="Nombre"
                  name="name"
                  value={form.name}
                  onChange={handleFieldChange}
                  placeholder="Tu nombre completo"
                />
                <EditableField
                  label="Teléfono"
                  name="telefono"
                  value={form.telefono}
                  onChange={handleFieldChange}
                  type="tel"
                  placeholder="+34 600 000 000"
                />
                <EditableField
                  label="Empresa"
                  name="empresa"
                  value={form.empresa}
                  onChange={handleFieldChange}
                  placeholder="Razón social"
                />

                {/* Divider */}
                <hr className="border-gray-200 my-1" />

                {/* Read-only fields */}
                <ReadOnlyField label="Email" value={profile.email} />
                <ReadOnlyField label="NIF / CIF" value={profile.nif_cif ?? ""} />
                <ReadOnlyField
                  label="Tipo de Tarifa"
                  value={profile.tipo_tarifa ?? "Sin tarifa asignada"}
                />
              </div>

              {/* Save button */}
              <div className="mt-8">
                <button
                  type="submit"
                  disabled={saving}
                  className="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed"
                  style={{ backgroundColor: "#E8751A" }}
                >
                  {saving ? (
                    <Loader2 size={16} className="animate-spin" />
                  ) : (
                    <Save size={16} />
                  )}
                  {saving ? "Guardando…" : "Guardar cambios"}
                </button>
              </div>
            </div>
          </form>
        ) : (
          <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
            <AlertCircle size={40} className="mx-auto text-gray-300 mb-3" />
            <p className="text-gray-500">No se pudo cargar el perfil.</p>
            <button
              onClick={fetchProfile}
              className="mt-3 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors hover:opacity-90"
              style={{ backgroundColor: "#E8751A" }}
            >
              Reintentar
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
