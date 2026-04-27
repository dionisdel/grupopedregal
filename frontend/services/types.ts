// Shared TypeScript interfaces for the e-commerce portal

export interface CategoryNode {
  id: number;
  nombre: string;
  slug: string;
  descripcion: string | null;
  imagen_banner_url: string | null;
  imagen_thumbnail_url: string | null;
  parent_id: number | null;
  orden: number;
  children: CategoryNode[];
  product_count: number;
}

export interface ProductListItem {
  id: number;
  codigo_articulo: string;
  nombre: string;
  slug: string;
  marca: string | null;
  imagen_url: string | null;
  pvp: number;
  pre_pvp: number;
  unidad: string | null;
  stock_total: number;
  filtros_dinamicos: Record<string, string>;
}

export interface ProductDetail extends ProductListItem {
  descripcion: string | null;
  proveedor: string | null;
  kg_litro: number | null;
  largo: number | null;
  ancho: number | null;
  metros_articulo: number | null;
  unidades_por_articulo: number | null;
  articulos_por_embalaje: number | null;
  unidades_palet: number | null;
  palet_retornable: boolean;
  iva_porcentaje: number;
  stock_por_almacen: { almacen: string; cantidad: number }[];
}

export interface CartItem {
  id?: number;
  product_id: number;
  nombre: string;
  slug: string;
  imagen_url: string | null;
  precio_unitario: number;
  cantidad: number;
  subtotal: number;
}

export interface PriceInputs {
  pvp_proveedor: number;
  desc_prov_1: number;
  coste_transporte: number;
  iva_porcentaje: number;
  desc_camion_vip: number;
  desc_camion: number;
  desc_oferta: number;
  desc_vip: number;
  desc_empresas: number;
  desc_empresas_a: number;
  metros_articulo: number | null;
}

export interface PriceOutputs {
  coste_neto: number;
  coste_neto_m2: number | null;
  coste_m2_trans: number | null;
  pre_pvp: number;
  pvp: number;
  neto_camion_vip: number;
  neto_camion: number;
  neto_oferta: number;
  neto_vip: number;
  neto_empresas: number;
  neto_empresas_a: number;
}

export interface CategoryByPathResponse {
  category: CategoryNode;
  breadcrumb: { id: number; nombre: string; slug: string }[];
  siblings: CategoryNode[];
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
