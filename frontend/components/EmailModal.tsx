"use client";

import { useState } from "react";
import { X, Send, Loader2, CheckCircle, AlertCircle } from "lucide-react";

interface EmailModalProps {
  open: boolean;
  onClose: () => void;
  onSend: (email: string) => Promise<void>;
}

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default function EmailModal({ open, onClose, onSend }: EmailModalProps) {
  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [sending, setSending] = useState(false);
  const [success, setSuccess] = useState(false);

  if (!open) return null;

  const handleClose = () => {
    setEmail("");
    setError(null);
    setSending(false);
    setSuccess(false);
    onClose();
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const trimmed = email.trim();
    if (!trimmed) {
      setError("Introduce una dirección de email.");
      return;
    }
    if (!EMAIL_REGEX.test(trimmed)) {
      setError("El formato del email no es válido.");
      return;
    }

    setSending(true);
    try {
      await onSend(trimmed);
      setSuccess(true);
    } catch (err: unknown) {
      const msg =
        err instanceof Error ? err.message : "No se pudo enviar el email. Inténtalo más tarde.";
      setError(msg);
    } finally {
      setSending(false);
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      onClick={handleClose}
      role="dialog"
      aria-modal="true"
      aria-label="Enviar presupuesto por email"
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
          Enviar presupuesto por email
        </h2>
        <p className="text-sm text-gray-500 mb-5">
          Introduce la dirección de email a la que deseas enviar el PDF.
        </p>

        {success ? (
          <div className="flex flex-col items-center gap-3 py-4">
            <CheckCircle size={40} className="text-green-500" />
            <p className="text-sm font-medium text-green-700 text-center">
              El presupuesto se ha enviado correctamente.
            </p>
            <button
              onClick={handleClose}
              className="mt-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-[#E8751A] hover:opacity-90 transition-colors"
            >
              Cerrar
            </button>
          </div>
        ) : (
          <form onSubmit={handleSubmit} noValidate>
            <label htmlFor="email-dest" className="block text-sm font-medium text-gray-600 mb-1">
              Email destino
            </label>
            <input
              id="email-dest"
              type="email"
              placeholder="ejemplo@empresa.com"
              value={email}
              onChange={(e) => {
                setEmail(e.target.value);
                if (error) setError(null);
              }}
              disabled={sending}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 focus:border-[#E8751A] transition-colors disabled:opacity-50"
            />

            {error && (
              <div className="flex items-center gap-1.5 mt-2 text-red-600">
                <AlertCircle size={14} />
                <span className="text-sm">{error}</span>
              </div>
            )}

            <button
              type="submit"
              disabled={sending}
              className="mt-4 w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold text-white bg-[#E8751A] hover:opacity-90 transition-colors disabled:opacity-60"
            >
              {sending ? (
                <>
                  <Loader2 size={16} className="animate-spin" />
                  Enviando…
                </>
              ) : (
                <>
                  <Send size={16} />
                  Enviar
                </>
              )}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
