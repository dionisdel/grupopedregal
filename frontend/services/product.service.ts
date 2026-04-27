import api from './axios-instance';
import type { ProductDetail } from './types';

export const productService = {
  async fetchBySlug(slug: string): Promise<ProductDetail> {
    const res = await api.get(`/api/products/${slug}`);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const p: any = res.data;
    return {
      ...p,
      nombre: p.nombre || p.descripcion || '',
      marca: typeof p.marca === 'object' && p.marca !== null ? p.marca.nombre || null : p.marca,
      proveedor: typeof p.proveedor === 'object' && p.proveedor !== null ? p.proveedor.nombre_comercial || null : p.proveedor,
      filtros_dinamicos: p.filtros_dinamicos || {},
      stock_total: p.stock_total ?? 0,
      stock_por_almacen: p.stock_por_almacen || [],
    } as ProductDetail;
  },

  async fetchStock(productId: number): Promise<{ almacen: string; cantidad: number }[]> {
    const res = await api.get<{ almacen: string; cantidad: number }[]>(
      `/api/products/${productId}/stock`,
    );
    return res.data;
  },
};
