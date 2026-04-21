"use client";

import { useState } from "react";
import { FileText, Download, Mail, Loader2 } from "lucide-react";
import { catalogService } from "@/services/catalog.service";
import EmailModal from "./EmailModal";

interface PdfActionsProps {
  productId: number;
  m2?: number;
  merma?: number;
}

function triggerDownload(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

export default function PdfActions({ productId, m2, merma }: PdfActionsProps) {
  const [loadingSheet, setLoadingSheet] = useState(false);
  const [loadingQuote, setLoadingQuote] = useState(false);
  const [emailOpen, setEmailOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleDownloadSheet = async () => {
    setError(null);
    setLoadingSheet(true);
    try {
      const blob = await catalogService.downloadProductPdf(productId);
      triggerDownload(blob, `ficha-producto-${productId}.pdf`);
    } catch {
      setError("No se pudo generar la ficha en PDF. Inténtalo de nuevo.");
    } finally {
      setLoadingSheet(false);
    }
  };

  const handleDownloadQuote = async () => {
    setError(null);
    setLoadingQuote(true);
    try {
      const blob = await catalogService.downloadProductPdf(productId, {
        m2,
        merma_porcentaje: merma,
      });
      triggerDownload(blob, `presupuesto-${productId}.pdf`);
    } catch {
      setError("No se pudo generar el presupuesto en PDF. Inténtalo de nuevo.");
    } finally {
      setLoadingQuote(false);
    }
  };

  const handleSendEmail = async (email: string) => {
    try {
      await catalogService.sendProductEmail(productId, email, {
        m2,
        merma_porcentaje: merma,
      });
    } catch (err: unknown) {
      // Extract server message if available
      let message = "No se pudo enviar el email. Inténtalo más tarde.";
      if (err && typeof err === "object" && "response" in err) {
        const resp = (err as { response?: { data?: { message?: string } } }).response;
        if (resp?.data?.message) message = resp.data.message;
      }
      throw new Error(message);
    }
  };

  return (
    <>
      <div className="space-y-2">
        {/* VER FICHA EN PDF — always available */}
        <button
          onClick={handleDownloadSheet}
          disabled={loadingSheet}
          className="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold text-white bg-[#E8751A] hover:opacity-90 transition-colors disabled:opacity-60"
        >
          {loadingSheet ? (
            <>
              <Loader2 size={16} className="animate-spin" />
              Generando…
            </>
          ) : (
            <>
              <FileText size={16} />
              VER FICHA EN PDF
            </>
          )}
        </button>

        {/* GENERAR PDF — uses m2/merma from calculator */}
        {m2 != null && m2 > 0 && (
          <button
            onClick={handleDownloadQuote}
            disabled={loadingQuote}
            className="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold text-[#E8751A] bg-white border-2 border-[#E8751A] hover:bg-orange-50 transition-colors disabled:opacity-60"
          >
            {loadingQuote ? (
              <>
                <Loader2 size={16} className="animate-spin" />
                Generando…
              </>
            ) : (
              <>
                <Download size={16} />
                GENERAR PDF
              </>
            )}
          </button>
        )}

        {/* Enviar por e-mail */}
        <button
          onClick={() => {
            setError(null);
            setEmailOpen(true);
          }}
          className="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
        >
          <Mail size={16} />
          Enviar por e-mail
        </button>

        {/* Error message */}
        {error && (
          <p className="text-sm text-red-500 text-center mt-1">{error}</p>
        )}
      </div>

      <EmailModal
        open={emailOpen}
        onClose={() => setEmailOpen(false)}
        onSend={handleSendEmail}
      />
    </>
  );
}
