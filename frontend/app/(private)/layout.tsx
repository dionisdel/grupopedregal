import "../globals.css";
import AuthGuard from "@/components/AuthGuard";
import Navbar from "@/components/Navbar";

export default function PrivateLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthGuard>
      <Navbar />
      {children}
    </AuthGuard>
  );
}
