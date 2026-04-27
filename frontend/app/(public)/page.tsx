"use client";

import { ArrowRight } from "lucide-react";

export default function LandingPage() {
  return (
    <section
      className="relative w-full overflow-hidden min-h-[60vh] flex items-center"
      style={{ background: "linear-gradient(135deg, #333333 0%, #1a1a1a 50%, #E8751A 100%)" }}
    >
      <div className="absolute top-[-80px] right-[-80px] w-[300px] h-[300px] rounded-full bg-[#E8751A]/10" />
      <div className="absolute bottom-[-60px] left-[-60px] w-[200px] h-[200px] rounded-full bg-white/5" />
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 flex flex-col items-center text-center gap-6 w-full">
        <span className="text-white/80 text-sm font-semibold tracking-[0.2em] uppercase">
          Grupo Pedregal
        </span>
        <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight max-w-3xl">
          El Almacén de los Profesionales del Yeso
        </h1>
        <p className="text-white/70 text-base sm:text-lg max-w-2xl leading-relaxed">
          Yesos, escayolas, aislamientos, placas PYL y preformados. Todo lo que
          necesitas para tu obra, con precios competitivos y servicio profesional.
        </p>
        <a
          href="/categorias/"
          className="mt-2 inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-bold text-sm transition-colors hover:opacity-90"
          style={{ backgroundColor: "#E8751A" }}
        >
          Explorar productos
          <ArrowRight size={18} />
        </a>
      </div>
    </section>
  );
}
