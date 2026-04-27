import CategoryPageClient from "./CategoryPageClient";

export function generateStaticParams() {
  // Return empty array — all category paths are resolved client-side
  return [{ slug: undefined }];
}

export default function CategoryPage({ params }: { params: Promise<{ slug?: string[] }> }) {
  return <CategoryPageClient params={params} />;
}
