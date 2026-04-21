import api from './axios-instance';

// --- Types ---

export interface Category {
  id: number;
  nombre: string;
  slug: string;
  descripcion_web: string | null;
  imagen_url: string | null;
  orden: number;
}

export interface FilterOption {
  id: number;
  nombre: string;
}

export interface FiltersResponse {
  subcategories: FilterOption[];
  brands: FilterOption[];
  suppliers: FilterOption[];
}

export interface CatalogProduct {
  id: number;
  nombre: string;
  slug: string;
  categoria: string | null;
  marca: string | null;
  unidad: string | null;
  precio_pvp: number | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ClientProduct {
  id: number;
  nombre: string;
  slug: string;
  categoria: string | null;
  marca: string | null;
  unidad: string | null;
  precio: number | null;
}

export interface CatalogFilters {
  category_id?: number | null;
  subcategory_id?: number | null;
  brand_id?: number | null;
  supplier_id?: number | null;
  search?: string;
  page?: number;
}

export interface ProductSpecs {
  peso_kg: number | null;
  largo_cm: number | null;
  ancho_cm: number | null;
  m2_por_unidad: number | null;
  unidades_por_embalaje: number | null;
  unidades_por_palet: number | null;
}

export interface ProductCode {
  tipo: string;
  codigo: string;
}

export interface ProductPrice {
  base: number;
  iva: number;
  total: number;
}

export interface MaterialBreakdown {
  descripcion: string;
  cantidad_por_m2: number;
  cantidad_total: number;
  unidad: string;
  precio_unitario: number;
  total: number;
}

export interface CalculatorResult {
  materiales: MaterialBreakdown[];
  subtotal_sin_merma: number;
  merma_porcentaje: number;
  total_con_merma: number;
}

export interface ProductDetail {
  id: number;
  nombre: string;
  slug: string;
  sku: string;
  descripcion: string | null;
  categoria: string | null;
  marca: string | null;
  unidad: string | null;
  specs: ProductSpecs | null;
  codigos: ProductCode[];
  precio: ProductPrice;
  imagen_principal_url: string | null;
}

export interface QuoteItem {
  id: number;
  producto: string | null;
  producto_slug: string | null;
  m2: number;
  merma_porcentaje: number;
  subtotal: number;
  total: number;
  fecha: string;
}

export interface UserProfile {
  name: string;
  email: string;
  telefono: string | null;
  empresa: string | null;
  nif_cif: string | null;
  tipo_tarifa: string | null;
}

export interface UpdateProfileData {
  name: string;
  telefono: string;
  empresa: string;
}

// --- Service ---

export const catalogService = {
  async getPublicCategories(): Promise<Category[]> {
    const res = await api.get('/api/categories/public');
    return Array.isArray(res.data) ? res.data : res.data.data ?? [];
  },

  async getCatalog(filters: CatalogFilters = {}): Promise<PaginatedResponse<CatalogProduct>> {
    const params: Record<string, string | number> = {};
    if (filters.category_id) params.category_id = filters.category_id;
    if (filters.subcategory_id) params.subcategory_id = filters.subcategory_id;
    if (filters.brand_id) params.brand_id = filters.brand_id;
    if (filters.supplier_id) params.supplier_id = filters.supplier_id;
    if (filters.search) params.search = filters.search;
    if (filters.page) params.page = filters.page;

    const res = await api.get<PaginatedResponse<CatalogProduct>>('/api/products/catalog', { params });
    return res.data;
  },

  async getFilters(categoryId?: number | null): Promise<FiltersResponse> {
    const params: Record<string, number> = {};
    if (categoryId) params.category_id = categoryId;

    const res = await api.get<FiltersResponse>('/api/products/filters', { params });
    return res.data;
  },

  async getProductDetail(id: number): Promise<ProductDetail> {
    const res = await api.get<ProductDetail>(`/api/products/${id}/detail`);
    return res.data;
  },

  async getProductBySlug(slug: string): Promise<ProductDetail> {
    // Search by slug in catalog to get the product ID, then fetch detail
    const catalog = await this.getCatalog({ search: slug, page: 1 });
    const match = catalog.data.find((p) => p.slug === slug);
    if (!match) {
      throw new Error('Producto no encontrado');
    }
    return this.getProductDetail(match.id);
  },

  async calculateMaterials(
    productId: number,
    m2: number,
    merma_porcentaje: number = 5,
  ): Promise<CalculatorResult> {
    const res = await api.post<CalculatorResult>(
      `/api/products/${productId}/calculate`,
      { m2, merma_porcentaje },
    );
    return res.data;
  },

  async downloadProductPdf(
    productId: number,
    options?: { m2?: number; merma_porcentaje?: number },
  ): Promise<Blob> {
    const res = await api.post(
      `/api/products/${productId}/pdf`,
      options ?? {},
      { responseType: 'blob' },
    );
    return res.data;
  },

  async sendProductEmail(
    productId: number,
    email: string,
    options?: { m2?: number; merma_porcentaje?: number },
  ): Promise<{ message: string }> {
    const res = await api.post<{ message: string }>(
      `/api/products/${productId}/send-email`,
      { email, ...options },
    );
    return res.data;
  },

  // --- Client (authenticated) endpoints ---

  async getClientProducts(filters: CatalogFilters = {}): Promise<PaginatedResponse<ClientProduct>> {
    const params: Record<string, string | number> = {};
    if (filters.category_id) params.category_id = filters.category_id;
    if (filters.subcategory_id) params.subcategory_id = filters.subcategory_id;
    if (filters.brand_id) params.brand_id = filters.brand_id;
    if (filters.supplier_id) params.supplier_id = filters.supplier_id;
    if (filters.search) params.search = filters.search;
    if (filters.page) params.page = filters.page;

    const res = await api.get<PaginatedResponse<ClientProduct>>('/api/client/products', { params });
    return res.data;
  },

  async getClientProductDetail(id: number): Promise<ProductDetail> {
    const res = await api.get<ProductDetail>(`/api/client/products/${id}/detail`);
    return res.data;
  },

  async getClientProductBySlug(slug: string): Promise<ProductDetail> {
    // Search by slug in client catalog to get the product ID, then fetch client detail
    const catalog = await this.getClientProducts({ search: slug, page: 1 });
    const match = catalog.data.find((p) => p.slug === slug);
    if (!match) {
      throw new Error('Producto no encontrado');
    }
    return this.getClientProductDetail(match.id);
  },

  async getQuotes(page: number = 1): Promise<PaginatedResponse<QuoteItem>> {
    const res = await api.get<PaginatedResponse<QuoteItem>>('/api/client/presupuestos', {
      params: { page },
    });
    return res.data;
  },

  async saveQuote(data: {
    product_id: number;
    m2: number;
    merma_porcentaje: number;
    subtotal: number;
    total: number;
    resultado_json: Record<string, unknown>;
  }): Promise<{ id: number; message: string }> {
    const res = await api.post<{ id: number; message: string }>(
      '/api/client/presupuestos',
      data,
    );
    return res.data;
  },

  async getProfile(): Promise<UserProfile> {
    const res = await api.get<UserProfile>('/api/client/profile');
    return res.data;
  },

  async updateProfile(data: UpdateProfileData): Promise<{ message: string }> {
    const res = await api.put<{ message: string }>('/api/client/profile', data);
    return res.data;
  },
};
