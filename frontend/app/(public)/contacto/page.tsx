"use client";

import { useState } from "react";
import {
  Phone,
  Mail,
  MapPin,
  Globe,
  Facebook,
  Wrench,
  Truck,
  HardHat,
  Loader2,
  AlertCircle,
  CheckCircle2,
  Send,
} from "lucide-react";
import api from "@/services/axios-instance";

// --- Types ---

interface ContactFormData {
  nombre: string;
  email: string;
  telefono: string;
  empresa: string;
  linea_negocio: string;
  asunto: string;
  mensaje: string;
}

interface FieldErrors {
  nombre?: string[];
  email?: string[];
  telefono?: string[];
  empresa?: string[];
  linea_negocio?: string[];
  asunto?: string[];
  mensaje?: string[];
}

// --- Constants ---

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const businessLines = [
  {
    name: "PEDREGAL",
    tagline: "¡El Almacén de los Profesionales del Yeso!",
    services: "Yesos y Escayolas, Aislamientos, Placas PYL, Preformados PYL",
    phone: "+34 611 97 93 29",
    email: "info@grupopedregal.es",
    web: "elalmacendelosprofesionalesdelyeso.com",
    facebook: "https://www.facebook.com/pedregal",
    icon: Wrench,
    color: "#E8751A",
  },
  {
    name: "Saturno PORT",
    tagline: "Distribuidores oficiales de RIPPA en España",
    services:
      "Mini-excavadoras, carretillas elevadoras y maquinaria — Distribuidores oficiales de RIPPA en España",
    phone: "+34 610 92 95 92",
    email: "saturnoport@gmail.com",
    facebook: "https://www.facebook.com/saturnoport",
    icon: Truck,
    color: "#333333",
  },
  {
    name: "Rentapró",
    tagline: "Maquinaria para la construcción",
    services: "Alquiler de todo tipo de maquinaria para la construcción",
    phone: "694 904 097",
    facebook: "https://www.facebook.com/rentapro",
    icon: HardHat,
    color: "#555555",
  },
];

const lineaOptions = [
  { value: "", label: "Selecciona una línea de negocio" },
  { value: "PEDREGAL", label: "PEDREGAL — Materiales de construcción" },
  { value: "Saturno PORT", label: "Saturno PORT — Maquinaria" },
  { value: "Rentapró", label: "Rentapró — Alquiler de maquinaria" },
  { value: "General", label: "General — Consulta general" },
];

// --- Group Presentation ---

function GroupPresentation() {
  return (
    <section
      className="relative w-full overflow-hidden"
      style={{
        background:
          "linear-gradient(135deg, #333333 0%, #1a1a1a 50%, #E8751A 100%)",
      }}
    >
      <div className="absolute top-[-80px] right-[-80px] w-[300px] h-[300px] rounded-full bg-[#E8751A]/10" />
      <div className="absolute bottom-[-60px] left-[-60px] w-[200px] h-[200px] rounded-full bg-white/5" />

      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 flex flex-col items-center text-center gap-4">
        <span className="text-white/80 text-sm font-semibold tracking-[0.2em] uppercase">
          Grupo Pedregal
        </span>
        <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight max-w-3xl">
          Contacto
        </h1>
        <p className="text-white/70 text-base sm:text-lg max-w-2xl leading-relaxed">
          Grupo empresarial que integra tres líneas de negocio especializadas en
          materiales de construcción, venta y alquiler de maquinaria. Estamos a
          tu disposición para cualquier consulta.
        </p>
        <div className="flex items-center gap-2 text-white/60 text-sm mt-2">
          <MapPin size={16} className="shrink-0" />
          <span>C/ Fontaneros, 2-4, Arahal, Sevilla, España</span>
        </div>
      </div>
    </section>
  );
}

// --- Business Line Cards ---

function BusinessLineCards() {
  return (
    <section className="py-12 sm:py-16 bg-[#F5F5F5]">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-2xl sm:text-3xl font-bold text-[#333333] text-center mb-3">
          Nuestras Líneas de Negocio
        </h2>
        <p className="text-gray-500 text-center mb-10 max-w-xl mx-auto">
          Tres divisiones especializadas para cubrir todas tus necesidades
        </p>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {businessLines.map((line) => {
            const Icon = line.icon;
            return (
              <div
                key={line.name}
                className="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-[#E8751A]/50 transition-all duration-200 p-6 flex flex-col"
              >
                {/* Header */}
                <div className="flex items-center gap-3 mb-3">
                  <div
                    className="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                    style={{ backgroundColor: line.color }}
                  >
                    <Icon size={20} className="text-white" />
                  </div>
                  <div>
                    <h3 className="text-[#333333] font-bold text-lg">
                      {line.name}
                    </h3>
                    <p className="text-gray-400 text-xs">{line.tagline}</p>
                  </div>
                </div>

                {/* Services */}
                <p className="text-gray-600 text-sm leading-relaxed mb-4 flex-1">
                  {line.services}
                </p>

                {/* Contact details */}
                <div className="flex flex-col gap-2 pt-3 border-t border-gray-100">
                  <a
                    href={`tel:${line.phone.replace(/\s/g, "")}`}
                    className="flex items-center gap-2 text-sm text-gray-600 hover:text-[#E8751A] transition-colors"
                  >
                    <Phone size={14} className="shrink-0 text-[#E8751A]" />
                    {line.phone}
                  </a>
                  {line.email && (
                    <a
                      href={`mailto:${line.email}`}
                      className="flex items-center gap-2 text-sm text-gray-600 hover:text-[#E8751A] transition-colors"
                    >
                      <Mail size={14} className="shrink-0 text-[#E8751A]" />
                      {line.email}
                    </a>
                  )}
                  {line.web && (
                    <a
                      href={`https://${line.web}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center gap-2 text-sm text-gray-600 hover:text-[#E8751A] transition-colors"
                    >
                      <Globe size={14} className="shrink-0 text-[#E8751A]" />
                      {line.web}
                    </a>
                  )}
                  <a
                    href={line.facebook}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2 text-sm text-gray-600 hover:text-[#E8751A] transition-colors"
                    aria-label={`Facebook de ${line.name}`}
                  >
                    <Facebook size={14} className="shrink-0 text-[#E8751A]" />
                    Facebook
                  </a>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

// --- Embedded Map ---

function EmbeddedMap() {
  return (
    <section className="py-12 sm:py-16 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-2xl sm:text-3xl font-bold text-[#333333] text-center mb-3">
          Nuestra Ubicación
        </h2>
        <p className="text-gray-500 text-center mb-8 max-w-xl mx-auto">
          Visítanos en nuestro almacén en Arahal, Sevilla
        </p>
        <div className="rounded-xl overflow-hidden shadow-sm border border-gray-200">
          <iframe
            title="Ubicación Grupo Pedregal — C/ Fontaneros, 2-4, Arahal, Sevilla, España"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3175.0!2d-5.5419!3d37.2617!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zQy8gRm9udGFuZXJvcywgMi00LCBBcmFoYWwsIFNldmlsbGE!5e0!3m2!1ses!2ses!4v1700000000000"
            width="100%"
            height="400"
            style={{ border: 0 }}
            allowFullScreen
            loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
          />
        </div>
        <div className="flex items-center justify-center gap-2 mt-4 text-gray-500 text-sm">
          <MapPin size={14} className="text-[#E8751A]" />
          C/ Fontaneros, 2-4, Arahal, Sevilla, España
        </div>
      </div>
    </section>
  );
}

// --- Contact Form ---

function ContactForm() {
  const [form, setForm] = useState<ContactFormData>({
    nombre: "",
    email: "",
    telefono: "",
    empresa: "",
    linea_negocio: "",
    asunto: "",
    mensaje: "",
  });
  const [loading, setLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const handleChange = (
    field: keyof ContactFormData,
    value: string
  ) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    if (fieldErrors[field as keyof FieldErrors]) {
      setFieldErrors((prev) => {
        const next = { ...prev };
        delete next[field as keyof FieldErrors];
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
    if (!form.linea_negocio) {
      errors.linea_negocio = ["Selecciona una línea de negocio."];
    }
    if (!form.asunto.trim()) {
      errors.asunto = ["El asunto es obligatorio."];
    }
    if (!form.mensaje.trim()) {
      errors.mensaje = ["El mensaje es obligatorio."];
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
      await api.post("/api/contact", {
        nombre: form.nombre.trim(),
        email: form.email.trim(),
        telefono: form.telefono.trim() || undefined,
        empresa: form.empresa.trim() || undefined,
        linea_negocio: form.linea_negocio,
        asunto: form.asunto.trim(),
        mensaje: form.mensaje.trim(),
      });
      setSuccess(true);
    } catch (err: unknown) {
      const axiosErr = err as {
        response?: {
          status?: number;
          data?: { errors?: FieldErrors; message?: string };
        };
      };
      if (axiosErr.response?.status === 422 && axiosErr.response.data?.errors) {
        setFieldErrors(axiosErr.response.data.errors);
      } else if (axiosErr.response?.status === 429) {
        setGeneralError("Demasiadas solicitudes. Inténtalo más tarde.");
      } else {
        setGeneralError(
          axiosErr.response?.data?.message ||
            "No se pudo enviar el mensaje. Inténtalo de nuevo."
        );
      }
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-8 text-center">
        <div className="flex justify-center mb-4">
          <CheckCircle2 size={48} className="text-green-500" />
        </div>
        <h3 className="text-xl font-bold text-[#333333] mb-2">
          ¡Mensaje enviado!
        </h3>
        <p className="text-sm text-gray-600 mb-6">
          Hemos recibido tu consulta. Nos pondremos en contacto contigo lo antes
          posible.
        </p>
        <button
          onClick={() => {
            setSuccess(false);
            setForm({
              nombre: "",
              email: "",
              telefono: "",
              empresa: "",
              linea_negocio: "",
              asunto: "",
              mensaje: "",
            });
          }}
          className="inline-block px-6 py-3 rounded-lg text-sm font-bold text-white bg-[#E8751A] hover:opacity-90 transition-colors"
        >
          Enviar otro mensaje
        </button>
      </div>
    );
  }

  const inputClass = (field: keyof FieldErrors) =>
    `w-full px-3 py-2 border rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50 ${
      fieldErrors[field] ? "border-red-400" : "border-gray-300"
    }`;

  return (
    <form
      onSubmit={handleSubmit}
      noValidate
      className="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-6 sm:p-8"
    >
      <h3 className="text-xl font-bold text-[#333333] mb-1">
        Envíanos un mensaje
      </h3>
      <p className="text-sm text-gray-500 mb-6">
        Rellena el formulario y te responderemos lo antes posible.
      </p>

      {generalError && (
        <div className="flex items-center gap-2 p-3 rounded-lg bg-red-50 text-red-700 mb-4">
          <AlertCircle size={16} className="shrink-0" />
          <span className="text-sm">{generalError}</span>
        </div>
      )}

      <div className="space-y-4">
        {/* Row: Nombre + Email */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label htmlFor="contact-nombre" className="block text-sm font-medium text-gray-600 mb-1">
              Nombre <span className="text-red-500">*</span>
            </label>
            <input
              id="contact-nombre"
              type="text"
              autoComplete="name"
              value={form.nombre}
              onChange={(e) => handleChange("nombre", e.target.value)}
              disabled={loading}
              className={inputClass("nombre")}
              placeholder="Tu nombre"
            />
            {fieldErrors.nombre && (
              <p className="mt-1 text-xs text-red-600">{fieldErrors.nombre[0]}</p>
            )}
          </div>
          <div>
            <label htmlFor="contact-email" className="block text-sm font-medium text-gray-600 mb-1">
              Email <span className="text-red-500">*</span>
            </label>
            <input
              id="contact-email"
              type="email"
              autoComplete="email"
              value={form.email}
              onChange={(e) => handleChange("email", e.target.value)}
              disabled={loading}
              className={inputClass("email")}
              placeholder="tu@email.com"
            />
            {fieldErrors.email && (
              <p className="mt-1 text-xs text-red-600">{fieldErrors.email[0]}</p>
            )}
          </div>
        </div>

        {/* Row: Teléfono + Empresa */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label htmlFor="contact-telefono" className="block text-sm font-medium text-gray-600 mb-1">
              Teléfono
            </label>
            <input
              id="contact-telefono"
              type="tel"
              autoComplete="tel"
              value={form.telefono}
              onChange={(e) => handleChange("telefono", e.target.value)}
              disabled={loading}
              className={inputClass("telefono")}
              placeholder="+34 600 000 000"
            />
            {fieldErrors.telefono && (
              <p className="mt-1 text-xs text-red-600">{fieldErrors.telefono[0]}</p>
            )}
          </div>
          <div>
            <label htmlFor="contact-empresa" className="block text-sm font-medium text-gray-600 mb-1">
              Empresa <span className="text-gray-400 text-xs">(opcional)</span>
            </label>
            <input
              id="contact-empresa"
              type="text"
              autoComplete="organization"
              value={form.empresa}
              onChange={(e) => handleChange("empresa", e.target.value)}
              disabled={loading}
              className={inputClass("empresa")}
              placeholder="Nombre de tu empresa"
            />
            {fieldErrors.empresa && (
              <p className="mt-1 text-xs text-red-600">{fieldErrors.empresa[0]}</p>
            )}
          </div>
        </div>

        {/* Línea de negocio */}
        <div>
          <label htmlFor="contact-linea" className="block text-sm font-medium text-gray-600 mb-1">
            Línea de negocio <span className="text-red-500">*</span>
          </label>
          <select
            id="contact-linea"
            value={form.linea_negocio}
            onChange={(e) => handleChange("linea_negocio", e.target.value)}
            disabled={loading}
            className={inputClass("linea_negocio")}
          >
            {lineaOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          {fieldErrors.linea_negocio && (
            <p className="mt-1 text-xs text-red-600">{fieldErrors.linea_negocio[0]}</p>
          )}
        </div>

        {/* Asunto */}
        <div>
          <label htmlFor="contact-asunto" className="block text-sm font-medium text-gray-600 mb-1">
            Asunto <span className="text-red-500">*</span>
          </label>
          <input
            id="contact-asunto"
            type="text"
            value={form.asunto}
            onChange={(e) => handleChange("asunto", e.target.value)}
            disabled={loading}
            className={inputClass("asunto")}
            placeholder="Asunto de tu consulta"
          />
          {fieldErrors.asunto && (
            <p className="mt-1 text-xs text-red-600">{fieldErrors.asunto[0]}</p>
          )}
        </div>

        {/* Mensaje */}
        <div>
          <label htmlFor="contact-mensaje" className="block text-sm font-medium text-gray-600 mb-1">
            Mensaje <span className="text-red-500">*</span>
          </label>
          <textarea
            id="contact-mensaje"
            rows={5}
            value={form.mensaje}
            onChange={(e) => handleChange("mensaje", e.target.value)}
            disabled={loading}
            className={inputClass("mensaje")}
            placeholder="Escribe tu mensaje aquí..."
          />
          {fieldErrors.mensaje && (
            <p className="mt-1 text-xs text-red-600">{fieldErrors.mensaje[0]}</p>
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
              Enviando…
            </>
          ) : (
            <>
              <Send size={16} />
              ENVIAR MENSAJE
            </>
          )}
        </button>
      </div>
    </form>
  );
}

// --- Social Media Links ---

function SocialLinks() {
  return (
    <div className="flex items-center justify-center gap-4 mt-8">
      {businessLines.map((line) => (
        <a
          key={line.name}
          href={line.facebook}
          target="_blank"
          rel="noopener noreferrer"
          className="flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-200 text-sm text-gray-600 hover:border-[#E8751A]/50 hover:text-[#E8751A] transition-all"
          aria-label={`Facebook de ${line.name}`}
        >
          <Facebook size={16} />
          {line.name}
        </a>
      ))}
    </div>
  );
}

// --- Main Contact Page ---

export default function ContactoPage() {
  return (
    <>
      <GroupPresentation />
      <BusinessLineCards />
      <EmbeddedMap />

      <section className="py-12 sm:py-16 bg-[#F5F5F5]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-2xl sm:text-3xl font-bold text-[#333333] text-center mb-3">
            ¿Tienes alguna consulta?
          </h2>
          <p className="text-gray-500 text-center mb-8 max-w-xl mx-auto">
            Escríbenos y te responderemos lo antes posible
          </p>

          <ContactForm />

          {/* Social media links */}
          <div className="mt-10 text-center">
            <p className="text-gray-500 text-sm mb-3">Síguenos en redes sociales</p>
            <SocialLinks />
          </div>

          {/* Group contact info */}
          <div className="mt-10 flex flex-wrap items-center justify-center gap-6 text-sm text-gray-500">
            <a
              href="tel:+34610929592"
              className="flex items-center gap-2 hover:text-[#E8751A] transition-colors"
            >
              <Phone size={14} className="text-[#E8751A]" />
              +34 610 92 95 92
            </a>
            <a
              href="mailto:info@grupopedregal.es"
              className="flex items-center gap-2 hover:text-[#E8751A] transition-colors"
            >
              <Mail size={14} className="text-[#E8751A]" />
              info@grupopedregal.es
            </a>
            <span className="flex items-center gap-2">
              <MapPin size={14} className="text-[#E8751A]" />
              C/ Fontaneros, 2-4, Arahal, Sevilla
            </span>
          </div>
        </div>
      </section>
    </>
  );
}
