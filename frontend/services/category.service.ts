import api from './axios-instance';
import type { CategoryNode, CategoryByPathResponse, PaginatedResponse, ProductListItem } from './types';

export const categoryService = {
  async fetchTree(): Promise<CategoryNode[]> {
    const res = await api.get<CategoryNode[]>('/api/categories/tree');
    return res.data;
  },

  async fetchByPath(path: string): Promise<CategoryByPathResponse> {
    const res = await api.get('/api/categories/by-path', {
      params: { path },
    });
    const data = res.data;
    // API returns flat object with category fields + breadcrumb + children
    // Map to CategoryByPathResponse format
    return {
      category: {
        id: data.id,
        nombre: data.nombre,
        slug: data.slug,
        descripcion: data.descripcion,
        imagen_banner_url: data.imagen_banner_url,
        imagen_thumbnail_url: data.imagen_thumbnail_url,
        parent_id: data.parent_id,
        orden: data.orden,
        children: (data.children || []).map((c: Record<string, unknown>) => ({
          id: c.id,
          nombre: c.nombre,
          slug: c.slug,
          descripcion: c.descripcion || null,
          imagen_banner_url: c.imagen_banner_url || null,
          imagen_thumbnail_url: c.imagen_thumbnail_url || null,
          parent_id: c.parent_id || null,
          orden: c.orden || 0,
          children: [],
          product_count: c.product_count || 0,
        })),
        product_count: data.product_count || 0,
      } as CategoryNode,
      breadcrumb: data.breadcrumb || [],
      siblings: data.siblings || [],
    };
  },

  async fetchProducts(
    categoryId: number,
    page: number = 1,
    filters?: Record<string, string>,
  ): Promise<PaginatedResponse<ProductListItem>> {
    const params: Record<string, string | number> = { page };
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        params[`filter[${key}]`] = value;
      });
    }
    const res = await api.get(
      `/api/categories/${categoryId}/products`,
      { params },
    );
    // Map API response to frontend ProductListItem format
    const data = res.data;
    if (data.data) {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      data.data = data.data.map((p: any) => ({
        ...p,
        nombre: p.nombre || p.descripcion || '',
        marca: typeof p.marca === 'object' && p.marca !== null ? p.marca.nombre || null : p.marca,
        filtros_dinamicos: p.filtros_dinamicos || {},
        stock_total: p.stock_total ?? 0,
      }));
    }
    return data as PaginatedResponse<ProductListItem>;
  },

  async fetchFilters(categoryId: number): Promise<Record<string, string[]>> {
    const res = await api.get<Record<string, string[]>>(
      `/api/categories/${categoryId}/filters`,
    );
    return res.data;
  },
};
