"use client";

import { createContext, useContext, useEffect, useState } from "react";
import api from "@/services/axios-instance";

interface UserRole {
  id: number;
  name: string;
  slug: string;
  permissions?: string[];
}

interface User {
  id: number;
  name: string;
  email: string;
  role?: UserRole | null;
}

interface UserContextType {
  user: User | null;
  loading: boolean;
  fetchUser: () => Promise<void>;
  clearUser: () => void;
}

const UserContext = createContext<UserContextType | undefined>(undefined);

export function UserProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchUser = async () => {
    setLoading(true);
    try {
      const token = typeof window !== "undefined" ? localStorage.getItem("token") : null;
      if (!token) { 
        setUser(null); 
        setLoading(false);
        return; 
      }
      const res = await api.get("/api/user");
      setUser(res.data);
    } catch {
      setUser(null);
      if (typeof window !== "undefined") {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
      }
    } finally {
      setLoading(false);
    }
  };

  const clearUser = () => {
    setUser(null);
    if (typeof window !== "undefined") {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    }
  };

  useEffect(() => { fetchUser(); }, []);

  return (
    <UserContext.Provider value={{ user, loading, fetchUser, clearUser }}>
      {children}
    </UserContext.Provider>
  );
}

export function useUser() {
  const ctx = useContext(UserContext);
  if (!ctx) throw new Error("useUser must be used within UserProvider");
  return ctx;
}
