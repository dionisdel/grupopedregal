import ClientProductDetailPage from "@/components/ClientProductDetailPage";

export async function generateStaticParams() {
  return [];
}

export default function Page() {
  return <ClientProductDetailPage />;
}
