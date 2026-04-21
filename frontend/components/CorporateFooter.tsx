"use client";

import Link from "next/link";
import { Phone, Mail, MapPin, Facebook, Globe } from "lucide-react";

const businessLines = [
  {
    name: "PEDREGAL",
    description:
      "¡El Almacén de los Profesionales del Yeso! Yesos, Escayolas, Aislamientos, Placas PYL, Preformados PYL.",
    phone: "+34 611 97 93 29",
    email: "info@grupopedregal.es",
    facebook: "https://www.facebook.com/pedregal",
  },
  {
    name: "Saturno PORT",
    description:
      "Mini-excavadoras, carretillas elevadoras y maquinaria. Distribuidores oficiales de RIPPA en España.",
    phone: "+34 610 92 95 92",
    email: "saturnoport@gmail.com",
    facebook: "https://www.facebook.com/saturnoport",
  },
  {
    name: "Rentapró",
    description:
      "Alquiler de todo tipo de maquinaria para la construcción.",
    phone: "694 904 097",
    facebook: "https://www.facebook.com/rentapro",
  },
];

const quickLinks = [
  { label: "Inicio", href: "/" },
  { label: "Productos", href: "/productos" },
  { label: "Contacto", href: "/contacto" },
  { label: "Área de Cliente", href: "/login" },
];

const CorporateFooter = () => {
  const currentYear = new Date().getFullYear();

  return (
    <footer
      className="w-full"
      style={{ backgroundColor: "#333333" }}
      role="contentinfo"
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Main grid: business lines + quick links + contact */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-6">
          {/* Business lines — 3 columns */}
          {businessLines.map((line) => (
            <div key={line.name} className="lg:col-span-1">
              <h3 className="text-[#E8751A] font-bold text-base mb-2">
                {line.name}
              </h3>
              <p className="text-white/70 text-sm leading-relaxed mb-3">
                {line.description}
              </p>
              <div className="flex flex-col gap-1.5">
                <a
                  href={`tel:${line.phone.replace(/\s/g, "")}`}
                  className="flex items-center gap-2 text-white/80 hover:text-white text-sm transition-colors"
                >
                  <Phone size={14} className="shrink-0" />
                  {line.phone}
                </a>
                {line.email && (
                  <a
                    href={`mailto:${line.email}`}
                    className="flex items-center gap-2 text-white/80 hover:text-white text-sm transition-colors"
                  >
                    <Mail size={14} className="shrink-0" />
                    {line.email}
                  </a>
                )}
                <a
                  href={line.facebook}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2 text-white/80 hover:text-white text-sm transition-colors"
                  aria-label={`Facebook de ${line.name}`}
                >
                  <Facebook size={14} className="shrink-0" />
                  Facebook
                </a>
              </div>
            </div>
          ))}

          {/* Quick Links */}
          <div className="lg:col-span-1">
            <h3 className="text-[#E8751A] font-bold text-base mb-2">
              Enlaces Rápidos
            </h3>
            <nav aria-label="Enlaces del footer" className="flex flex-col gap-2">
              {quickLinks.map((link) => (
                <Link
                  key={link.href}
                  href={link.href}
                  className="text-white/80 hover:text-white text-sm transition-colors"
                >
                  {link.label}
                </Link>
              ))}
            </nav>
          </div>

          {/* Group contact info */}
          <div className="lg:col-span-1">
            <h3 className="text-[#E8751A] font-bold text-base mb-2">
              Grupo Pedregal
            </h3>
            <div className="flex flex-col gap-2">
              <div className="flex items-start gap-2 text-white/80 text-sm">
                <MapPin size={14} className="shrink-0 mt-0.5" />
                <span>C/ Fontaneros, 2-4, Arahal, Sevilla, España</span>
              </div>
              <a
                href="tel:+34610929592"
                className="flex items-center gap-2 text-white/80 hover:text-white text-sm transition-colors"
              >
                <Phone size={14} className="shrink-0" />
                +34 610 92 95 92
              </a>
              <a
                href="mailto:info@grupopedregal.es"
                className="flex items-center gap-2 text-white/80 hover:text-white text-sm transition-colors"
              >
                <Mail size={14} className="shrink-0" />
                info@grupopedregal.es
              </a>
              <a
                href="https://www.grupopedregal.es"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2 text-white/80 hover:text-white text-sm transition-colors"
              >
                <Globe size={14} className="shrink-0" />
                www.grupopedregal.es
              </a>
            </div>
          </div>
        </div>
      </div>

      {/* Copyright bar */}
      <div className="border-t border-white/10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <p className="text-white/60 text-sm text-center">
            © {currentYear} Grupo Pedregal. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </footer>
  );
};

export default CorporateFooter;
