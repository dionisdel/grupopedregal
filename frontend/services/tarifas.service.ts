import axios from './axios-instance';

export interface CustomerType {
  id: number;
  nombre: string;
  codigo: string;
}

export interface Category {
  id: number;
  nombre: string;
  codigo: string;
}

export interface Brand {
  id: number;
  nombre: string;
}

export interface ProductPrice {
  id: number;
  precio_base: number;
  precio_neto: number;
  margen_porcentaje: number;
  tipo_cliente_id: number;
  tipoCliente: CustomerType;
}

export interface Product {
  id: number;
  sku: string;
  nombre: string;
  categoria: Category;
  marca: Brand;
  unidadBase: {
    codigo: string;
  };
  precios: ProductPrice[];
}

export interface TarifasFilters {
  search?: string;
  categoria_id?: number;
  marca_id?: number;
  tipo_cliente_id?: number;
  page?: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export const tarifasService = {
  async getTarifas(filters: TarifasFilters = {}): Promise<PaginatedResponse<Product>> {
    const response = await axios.get<PaginatedResponse<Product>>('/api/tarifas', {
      params: filters,
    });
    return response.data;
  },

  async exportTarifas(filters: TarifasFilters = {}): Promise<Blob> {
    const response = await axios.get('/api/tarifas/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },

  async getCustomerTypes(): Promise<CustomerType[]> {
    const response = await axios.get<CustomerType[]>('/api/tarifas/customer-types');
    return response.data;
  },

  async getCategories(): Promise<Category[]> {
    const response = await axios.get<Category[]>('/api/tarifas/categories');
    return response.data;
  },

  async getBrands(): Promise<Brand[]> {
    const response = await axios.get<Brand[]>('/api/tarifas/brands');
    return response.data;
  },
};
