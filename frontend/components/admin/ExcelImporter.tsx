"use client";

import { useState, useRef } from "react";
import { Upload, FileSpreadsheet, CheckCircle, AlertTriangle, X } from "lucide-react";
import { adminService } from "@/services/admin.service";

interface ImportResult {
  created: number;
  updated: number;
  errors: { row: number; message: string }[];
}

interface ExcelImporterProps {
  onImportComplete?: () => void;
}

export default function ExcelImporter({ onImportComplete }: ExcelImporterProps) {
  const [file, setFile] = useState<File | null>(null);
  const [importing, setImporting] = useState(false);
  const [result, setResult] = useState<ImportResult | null>(null);
  const [error, setError] = useState<string | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selected = e.target.files?.[0];
    if (selected) {
      setFile(selected);
      setResult(null);
      setError(null);
    }
  };

  const handleImport = async () => {
    if (!file) return;
    setImporting(true);
    setError(null);
    setResult(null);
    try {
      const res = await adminService.importExcel(file);
      setResult(res);
      onImportComplete?.();
    } catch (err: unknown) {
      const msg =
        err && typeof err === "object" && "response" in err
          ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
          : undefined;
      setError(msg || "Error al importar archivo");
    } finally {
      setImporting(false);
    }
  };

  const reset = () => {
    setFile(null);
    setResult(null);
    setError(null);
    if (inputRef.current) inputRef.current.value = "";
  };

  return (
    <div className="bg-white border border-gray-200 rounded-xl p-5">
      <div className="flex items-center gap-2 mb-4">
        <FileSpreadsheet size={18} className="text-[#E8751A]" />
        <h3 className="text-sm font-semibold text-[#333]">Importar desde Excel</h3>
      </div>

      {/* File input */}
      <div className="flex items-center gap-3 mb-4">
        <label className="flex items-center gap-2 px-4 py-2 border border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-[#E8751A] hover:bg-orange-50/30 transition-colors">
          <Upload size={16} className="text-gray-400" />
          <span className="text-sm text-gray-600">
            {file ? file.name : "Seleccionar archivo .xlsx / .xls"}
          </span>
          <input
            ref={inputRef}
            type="file"
            accept=".xlsx,.xls"
            className="hidden"
            onChange={handleFileChange}
          />
        </label>
        {file && (
          <button onClick={reset} className="p-1 text-gray-400 hover:text-gray-600">
            <X size={16} />
          </button>
        )}
      </div>

      {/* Import button */}
      <button
        onClick={handleImport}
        disabled={!file || importing}
        className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[#E8751A] rounded-lg hover:opacity-90 disabled:opacity-50 transition-opacity"
      >
        {importing ? (
          <>
            <span className="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
            Importando...
          </>
        ) : (
          <>
            <Upload size={16} />
            Importar
          </>
        )}
      </button>

      {/* Error */}
      {error && (
        <div className="mt-4 text-sm text-red-600 bg-red-50 px-4 py-3 rounded-lg">
          {error}
        </div>
      )}

      {/* Results */}
      {result && (
        <div className="mt-4 space-y-3">
          <div className="flex items-center gap-4 text-sm">
            <span className="flex items-center gap-1 text-green-600">
              <CheckCircle size={16} />
              Creados: {result.created}
            </span>
            <span className="flex items-center gap-1 text-blue-600">
              <CheckCircle size={16} />
              Actualizados: {result.updated}
            </span>
            {result.errors.length > 0 && (
              <span className="flex items-center gap-1 text-red-500">
                <AlertTriangle size={16} />
                Errores: {result.errors.length}
              </span>
            )}
          </div>

          {/* Error table */}
          {result.errors.length > 0 && (
            <div className="border border-red-200 rounded-lg overflow-hidden">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-red-50">
                    <th className="text-left px-3 py-2 text-xs font-semibold text-red-700 w-20">
                      Fila
                    </th>
                    <th className="text-left px-3 py-2 text-xs font-semibold text-red-700">
                      Error
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {result.errors.map((err, i) => (
                    <tr key={i} className="border-t border-red-100">
                      <td className="px-3 py-2 text-red-600 font-mono">{err.row}</td>
                      <td className="px-3 py-2 text-red-600">{err.message}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
