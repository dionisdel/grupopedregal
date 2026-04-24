import { Suspense } from "react";
import ClientProductExplorerClient from "@/components/ClientProductExplorerClient";

export default function ClientProductosPage() {
  return (
    <Suspense>
      <ClientProductExplorerClient />
    </Suspense>
  );
}
