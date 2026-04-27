import api from './axios-instance';
import type { CartItem } from './types';

export interface CartResponse {
  id: number;
  items: CartItem[];
}

export const cartService = {
  async getCart(): Promise<CartResponse> {
    const res = await api.get<CartResponse>('/api/cart');
    return res.data;
  },

  async addItem(productId: number, cantidad: number = 1): Promise<CartItem> {
    const res = await api.post<CartItem>('/api/cart/items', {
      product_id: productId,
      cantidad,
    });
    return res.data;
  },

  async updateItem(itemId: number, cantidad: number): Promise<CartItem> {
    const res = await api.put<CartItem>(`/api/cart/items/${itemId}`, {
      cantidad,
    });
    return res.data;
  },

  async removeItem(itemId: number): Promise<void> {
    await api.delete(`/api/cart/items/${itemId}`);
  },

  async mergeCart(items: { product_id: number; cantidad: number }[]): Promise<CartResponse> {
    const res = await api.post<CartResponse>('/api/cart/merge', { items });
    return res.data;
  },
};
