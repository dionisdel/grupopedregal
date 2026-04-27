import api from './axios-instance';
import type { CategoryNode, ProductListItem, ProductDetail, PaginatedResponse } from './types';

export const adminService = {
  // Categories
  async getCategories(): Promise<CategoryNode[]> {
    const res = await api.get<CategoryNode[]>('/api/admin/categories');
    return res.data;
  },

  async createCategory(data: Partial<CategoryNode>): Promise<CategoryNode> {
    const res = await api.post<CategoryNode>('/api/admin/categories', data);
    return res.data;
  },

  async updateCategory(id: number, data: Partial<CategoryNode>): Promise<CategoryNode> {
    const res = await api.put<CategoryNode>(`/api/admin/categories/${id}`, data);
    return res.data;
  },

  async deleteCategory(id: number): Promise<void> {
    await api.delete(`/api/admin/categories/${id}`);
  },

  async reorderCategories(order: { id: number; parent_id: number | null; orden: number }[]): Promise<void> {
    await api.put('/api/admin/categories/reorder', { order });
  },

  // Products
  async getProducts(params?: Record<string, string | number>): Promise<PaginatedResponse<ProductListItem>> {
    const res = await api.get('/api/admin/products', { params });
    const data = res.data;
    if (data.data) {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      data.data = data.data.map((p: any) => ({
        ...p,
        nombre: p.nombre || p.descripcion || '',
        marca: typeof p.marca === 'object' && p.marca !== null ? p.marca.nombre || null : p.marca,
      }));
    }
    return data as PaginatedResponse<ProductListItem>;
  },

  async createProduct(data: Partial<ProductDetail>): Promise<ProductDetail> {
    const res = await api.post<ProductDetail>('/api/admin/products', data);
    return res.data;
  },

  async updateProduct(id: number, data: Partial<ProductDetail>): Promise<ProductDetail> {
    const res = await api.put<ProductDetail>(`/api/admin/products/${id}`, data);
    return res.data;
  },

  async deleteProduct(id: number): Promise<void> {
    await api.delete(`/api/admin/products/${id}`);
  },

  async importExcel(file: File): Promise<{ created: number; updated: number; errors: { row: number; message: string }[] }> {
    const formData = new FormData();
    formData.append('file', file);
    const res = await api.post('/api/admin/products/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return res.data;
  },

  async uploadImage(file: File, type: string = 'product'): Promise<{ url: string }> {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', type);
    const res = await api.post<{ url: string }>('/api/admin/images/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return res.data;
  },

  async getPriceHistory(productId: number): Promise<{
    field_changed: string;
    old_value: number | null;
    new_value: number | null;
    changed_at: string;
    changed_by: string | null;
  }[]> {
    const res = await api.get(`/api/admin/products/${productId}/price-history`);
    return res.data;
  },
};
