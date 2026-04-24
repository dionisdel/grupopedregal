import { Suspense } from "react";
import ClientProductDetailPage from "@/components/ClientProductDetailPage";

export default function ClientProductDetailRoute() {
  return (
    <Suspense>
      <ClientProductDetailPage />
    </Suspense>
  );
}
