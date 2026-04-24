import type { Metadata } from "next";
import "./globals.css";
import { UserProvider } from "@/context/UserContext";

export const metadata: Metadata = {
  title: "Grupo Pedregal — El Almacén de los Profesionales del Yeso",
  description:
    "Portal web de Grupo Pedregal. Explora nuestro catálogo de materiales de construcción, consulta precios, calcula cantidades y genera presupuestos. PEDREGAL · Saturno PORT · Rentapró.",
  icons: {
    icon: "/favicon.png",
  },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="es">
      <body>
        <UserProvider>{children}</UserProvider>
      </body>
    </html>
  );
}
