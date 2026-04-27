import axios from './axios-instance';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: {
    id: number;
    name: string;
    slug: string;
    permissions?: string[];
  } | null;
}

export interface LoginResponse {
  user: User;
  token: string;
}

export interface RegisterData {
  nombre: string;
  email: string;
  telefono: string;
  empresa: string;
  nif_cif: string;
  password: string;
}

export const authService = {
  async register(data: RegisterData): Promise<{ message: string }> {
    const response = await axios.post<{ message: string }>('/api/register', data);
    return response.data;
  },

  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await axios.post<LoginResponse>('/api/login', credentials);
    
    // Guardar token en localStorage
    if (response.data.token) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
    }
    
    return response.data;
  },

  async logout(): Promise<void> {
    try {
      await axios.post('/api/logout');
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  },

  async getCurrentUser(): Promise<User> {
    const response = await axios.get<User>('/api/user');
    localStorage.setItem('user', JSON.stringify(response.data));
    return response.data;
  },

  getToken(): string | null {
    return localStorage.getItem('token');
  },

  getUser(): User | null {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  },

  isAuthenticated(): boolean {
    return !!this.getToken();
  },

  hasPermission(permission: string): boolean {
    const user = this.getUser();
    return user?.role?.permissions?.includes(permission) ?? false;
  },

  hasRole(roleSlug: string): boolean {
    const user = this.getUser();
    return user?.role?.slug === roleSlug;
  },
};
