import { Suspense } from "react";
import ProductDetailClient from "@/components/ProductDetailClient";

export default function ProductDetailPage() {
  return (
    <Suspense>
      <ProductDetailClient />
    </Suspense>
  );
}
