import { Suspense } from "react";
import ProductExplorerClient from "@/components/ProductExplorerClient";

export default function ProductosPage() {
  return (
    <Suspense>
      <ProductExplorerClient />
    </Suspense>
  );
}
