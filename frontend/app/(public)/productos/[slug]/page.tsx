import ProductDetailClient from "@/components/ProductDetailClient";

export async function generateStaticParams() {
  return [];
}

export default function ProductDetailPage() {
  return <ProductDetailClient />;
}
