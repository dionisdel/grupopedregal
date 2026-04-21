import "../globals.css";
import AuthGuard from "@/components/AuthGuard";
import CorporateNavbar from "@/components/CorporateNavbar";

export default function PrivateLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthGuard>
      <CorporateNavbar />
      {children}
    </AuthGuard>
  );
}
