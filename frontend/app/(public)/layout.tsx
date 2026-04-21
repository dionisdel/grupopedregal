import "../globals.css";
import CorporateNavbar from "@/components/CorporateNavbar";
import CorporateFooter from "@/components/CorporateFooter";

export default function PublicLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex flex-col min-h-screen">
      <CorporateNavbar />
      <main className="flex-1">{children}</main>
      <CorporateFooter />
    </div>
  );
}
