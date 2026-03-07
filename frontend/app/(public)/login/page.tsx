"use client";

import { manrope } from "@/app/fonts";
import api from "@/services/axios-instance";
import { useUser } from "@/context/UserContext";
import { useRouter } from "next/navigation";
import { ChangeEvent, FormEvent, useState } from "react";

export default function LoginPage() {
  const [data, setData] = useState({ email: "", password: "" });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const { fetchUser } = useUser();
  const router = useRouter();

  const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setData((prev) => ({ ...prev, [name]: value }));
    setError("");
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");
    try {
      const res = await api.post("/login", data);
      const token = res.data.token;
      if (token) {
        localStorage.setItem("token", token);
        await fetchUser();
        router.push("/app/home");
      }
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } };
      setError(axiosErr.response?.data?.message || "Credenciales incorrectas");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="h-full min-h-[calc(100vh-80px)] flex items-center justify-center px-5">
      <form
        onSubmit={handleSubmit}
        className="flex flex-col items-center justify-center gap-8 py-16 lg:max-w-[466px] w-full m-auto"
      >
        <h1 className={`text-[40px] font-semibold text-white ${manrope.className}`}>
          Acceso
        </h1>

        <div className="flex flex-col gap-4 w-full bg-white/[0.04] rounded-xl p-5">
          {/* Email */}
          <div className="flex items-center gap-2 rounded-xl bg-white/[0.22] border border-[rgba(236,220,146,0.15)] px-3 h-[62px] w-full focus-within:border-[#ECDC92]/50 transition-colors">
            <input
              className="w-full bg-transparent outline-none placeholder:text-white/50 text-white/80"
              placeholder="Email"
              type="email"
              name="email"
              value={data.email}
              onChange={handleChange}
              required
            />
          </div>

          {/* Password */}
          <div className="flex items-center gap-2 rounded-xl bg-white/[0.22] border border-[rgba(236,220,146,0.15)] px-3 h-[62px] w-full focus-within:border-[#ECDC92]/50 transition-colors">
            <input
              className="w-full bg-transparent outline-none placeholder:text-white/50 text-white/80"
              placeholder="Contraseña"
              type="password"
              name="password"
              value={data.password}
              onChange={handleChange}
              required
            />
          </div>

          {error && (
            <p className="text-red-400 text-sm text-center">{error}</p>
          )}

          {/* Submit */}
          <button
            type="submit"
            disabled={loading || !data.email || !data.password}
            className="flex items-center justify-center w-full rounded-[9px] cursor-pointer hover:opacity-90 py-4 bg-primary disabled:opacity-60 disabled:cursor-not-allowed transition-opacity"
          >
            {loading ? (
              <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            ) : (
              <span className="text-[16px] font-semibold text-white">Iniciar Sesión</span>
            )}
          </button>
        </div>
      </form>
    </div>
  );
}
