'use client';

import { useState, useEffect, useCallback } from 'react';
import { tarifasService, type Product, type CustomerType, type Category, type Brand, type TarifasFilters } from '@/services/tarifas.service';
import { Download, Search, ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-react';

type SortField = 'sku' | 'nombre' | 'categoria' | 'marca' | 'precio';
type SortOrder = 'asc' | 'desc';

export default function TarifasPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const [masterLoaded, setMasterLoaded] = useState(false);
  const [customerTypes, setCustomerTypes] = useState<CustomerType[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [sortField, setSortField] = useState<SortField>('nombre');
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc');
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const [filters, setFilters] = useState<TarifasFilters>({
    search: '',
    categoria_id: undefined,
    marca_id: undefined,
    tipo_cliente_id: undefined,
    page: 1,
  });

  // Cargar datos maestros primero, luego tarifas
  useEffect(() => {
    const init = async () => {
      try {
        const [typesData, categoriesData, brandsData] = await Promise.all([
          tarifasService.getCustomerTypes(),
          tarifasService.getCategories(),
          tarifasService.getBrands(),
        ]);
        setCustomerTypes(typesData);
        setCategories(categoriesData);
        setBrands(brandsData);
      } catch (error) {
        console.error('Error loading master data:', error);
      } finally {
        setMasterLoaded(true);
      }
    };
    init();
  }, []);

  // Cargar tarifas cuando los datos maestros estén listos o cambien los filtros
  useEffect(() => {
    if (!masterLoaded) return;
    loadTarifas();
  }, [filters, masterLoaded]);

  const loadTarifas = async () => {
    setLoading(true);
    try {
      const response = await tarifasService.getTarifas(filters);
      setProducts(response.data);
      setCurrentPage(response.current_page);
      setLastPage(response.last_page);
      setTotal(response.total);
    } catch (error) {
      console.error('Error loading tarifas:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleExport = async () => {
    try {
      const blob = await tarifasService.exportTarifas(filters);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `TARIFA_${new Date().toISOString().split('T')[0]}.xlsx`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error('Error exporting:', error);
    }
  };

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortOrder('asc');
    }
  };

  const goToPage = (page: number) => {
    setFilters(prev => ({ ...prev, page }));
  };

  const sortedProducts = [...products].sort((a, b) => {
    let aVal: any, bVal: any;
    switch (sortField) {
      case 'sku': aVal = a.sku; bVal = b.sku; break;
      case 'nombre': aVal = a.nombre; bVal = b.nombre; break;
      case 'categoria': aVal = a.categoria?.nombre || ''; bVal = b.categoria?.nombre || ''; break;
      case 'marca': aVal = a.marca?.nombre || ''; bVal = b.marca?.nombre || ''; break;
      case 'precio':
        aVal = filters.tipo_cliente_id ? (a.precios.find(p => p.tipo_cliente_id === filters.tipo_cliente_id)?.precio_neto || 0) : 0;
        bVal = filters.tipo_cliente_id ? (b.precios.find(p => p.tipo_cliente_id === filters.tipo_cliente_id)?.precio_neto || 0) : 0;
        break;
    }
    if (typeof aVal === 'string') return sortOrder === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    return sortOrder === 'asc' ? aVal - bVal : bVal - aVal;
  });

  const SortBtn = ({ field, label }: { field: SortField; label: string }) => (
    <button
      onClick={() => handleSort(field)}
      className="flex items-center gap-1 group font-semibold text-xs text-gray-500 uppercase tracking-wider hover:text-gray-800 transition-colors"
    >
      {label}
      {sortField === field
        ? sortOrder === 'asc' ? <ChevronUp size={14} className="text-blue-600" /> : <ChevronDown size={14} className="text-blue-600" />
        : <ChevronsUpDown size={14} className="opacity-30 group-hover:opacity-60" />
      }
    </button>
  );

  // Paginación estilo imagen
  const renderPages = () => {
    const pages: (number | '...')[] = [];
    if (lastPage <= 7) {
      for (let i = 1; i <= lastPage; i++) pages.push(i);
    } else {
      pages.push(1, 2);
      if (currentPage > 4) pages.push('...');
      for (let i = Math.max(3, currentPage - 1); i <= Math.min(lastPage - 2, currentPage + 1); i++) pages.push(i);
      if (currentPage < lastPage - 3) pages.push('...');
      pages.push(lastPage - 1, lastPage);
    }
    return pages;
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto space-y-6">

        {/* Search bar + filters */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <div className="flex flex-wrap gap-4 items-end">
            {/* Search */}
            <div className="flex-1 min-w-[220px]">
              <label className="block text-xs font-semibold text-gray-500 mb-1.5">¿Qué estás buscando?</label>
              <div className="relative">
                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  placeholder="Buscar por nombre, SKU..."
                  className="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                  value={filters.search}
                  onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value, page: 1 }))}
                />
              </div>
            </div>

            {/* Categoría */}
            <div className="min-w-[160px]">
              <label className="block text-xs font-semibold text-gray-500 mb-1.5">Categoría</label>
              <select
                className="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white transition"
                value={filters.categoria_id || ''}
                onChange={(e) => setFilters(prev => ({ ...prev, categoria_id: e.target.value ? Number(e.target.value) : undefined, page: 1 }))}
              >
                <option value="">Todas</option>
                {categories.map(c => <option key={c.id} value={c.id}>{c.nombre}</option>)}
              </select>
            </div>

            {/* Marca */}
            <div className="min-w-[140px]">
              <label className="block text-xs font-semibold text-gray-500 mb-1.5">Marca</label>
              <select
                className="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white transition"
                value={filters.marca_id || ''}
                onChange={(e) => setFilters(prev => ({ ...prev, marca_id: e.target.value ? Number(e.target.value) : undefined, page: 1 }))}
              >
                <option value="">Todas</option>
                {brands.map(b => <option key={b.id} value={b.id}>{b.nombre}</option>)}
              </select>
            </div>

            {/* Tipo cliente */}
            <div className="min-w-[160px]">
              <label className="block text-xs font-semibold text-gray-500 mb-1.5">Tipo de cliente</label>
              <select
                className="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white transition"
                value={filters.tipo_cliente_id || ''}
                onChange={(e) => setFilters(prev => ({ ...prev, tipo_cliente_id: e.target.value ? Number(e.target.value) : undefined, page: 1 }))}
              >
                <option value="">Todos</option>
                {customerTypes.map(t => <option key={t.id} value={t.id}>{t.nombre}</option>)}
              </select>
            </div>

            {/* Search button */}
            <button
              onClick={loadTarifas}
              className="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-colors"
            >
              BUSCAR
            </button>
          </div>
        </div>

        {/* Table card */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          {/* Table header bar */}
          <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
              <h2 className="text-base font-bold text-gray-800">Tarifas de Productos</h2>
              {!loading && <p className="text-xs text-gray-400 mt-0.5">{total} productos encontrados</p>}
            </div>
            <div className="flex items-center gap-3">
              {/* Pagination top-right */}
              {lastPage > 1 && (
                <div className="flex items-center gap-1">
                  <button
                    disabled={currentPage <= 1}
                    onClick={() => goToPage(currentPage - 1)}
                    className="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                  >
                    <ChevronUp size={14} className="rotate-[-90deg]" />
                  </button>
                  {renderPages().map((p, i) =>
                    p === '...'
                      ? <span key={`dots-${i}`} className="w-8 h-8 flex items-center justify-center text-gray-400 text-sm">...</span>
                      : <button
                          key={p}
                          onClick={() => goToPage(p as number)}
                          className={`w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition ${
                            currentPage === p
                              ? 'bg-blue-600 text-white'
                              : 'border border-gray-200 text-gray-600 hover:bg-gray-50'
                          }`}
                        >{p}</button>
                  )}
                  <button
                    disabled={currentPage >= lastPage}
                    onClick={() => goToPage(currentPage + 1)}
                    className="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                  >
                    <ChevronDown size={14} className="rotate-[-90deg]" />
                  </button>
                </div>
              )}

              {/* Export */}
              <button
                onClick={handleExport}
                className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-colors"
              >
                <Download size={15} />
                Exportar Excel
              </button>
            </div>
          </div>

          {/* Table */}
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-100">
                  <th className="px-6 py-3 text-left"><SortBtn field="sku" label="SKU" /></th>
                  <th className="px-6 py-3 text-left"><SortBtn field="nombre" label="Producto" /></th>
                  <th className="px-6 py-3 text-left"><SortBtn field="categoria" label="Categoría" /></th>
                  <th className="px-6 py-3 text-left"><SortBtn field="marca" label="Marca" /></th>
                  {filters.tipo_cliente_id && (
                    <th className="px-6 py-3 text-right"><SortBtn field="precio" label="Precio Neto" /></th>
                  )}
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  // Skeleton rows
                  Array.from({ length: 10 }).map((_, i) => (
                    <tr key={i} className="border-b border-gray-50">
                      {[1,2,3,4].map(j => (
                        <td key={j} className="px-6 py-4">
                          <div className="h-4 bg-gray-100 rounded animate-pulse" style={{ width: `${60 + Math.random() * 30}%` }} />
                        </td>
                      ))}
                    </tr>
                  ))
                ) : sortedProducts.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="px-6 py-16 text-center text-gray-400 text-sm">
                      No se encontraron productos
                    </td>
                  </tr>
                ) : (
                  sortedProducts.map((product, idx) => {
                    const precio = filters.tipo_cliente_id
                      ? product.precios.find(p => p.tipo_cliente_id === filters.tipo_cliente_id)
                      : null;
                    return (
                      <tr
                        key={product.id}
                        className={`border-b border-gray-50 hover:bg-blue-50/40 transition-colors ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}
                      >
                        <td className="px-6 py-3.5 text-sm font-mono text-gray-500">{product.sku}</td>
                        <td className="px-6 py-3.5 text-sm font-medium text-gray-800">{product.nombre}</td>
                        <td className="px-6 py-3.5 text-sm text-gray-500">{product.categoria?.nombre || '—'}</td>
                        <td className="px-6 py-3.5 text-sm text-gray-500">{product.marca?.nombre || '—'}</td>
                        {filters.tipo_cliente_id && (
                          <td className="px-6 py-3.5 text-sm text-right font-semibold text-gray-800">
                            {precio ? `${precio.precio_neto.toFixed(2)} €` : '—'}
                          </td>
                        )}
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>

          {/* Bottom pagination */}
          {lastPage > 1 && !loading && (
            <div className="flex items-center justify-between px-6 py-4 border-t border-gray-100">
              <p className="text-sm text-gray-400">
                Página {currentPage} de {lastPage} · {total} productos
              </p>
              <div className="flex items-center gap-1">
                <button
                  disabled={currentPage <= 1}
                  onClick={() => goToPage(currentPage - 1)}
                  className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                  Anterior
                </button>
                {renderPages().map((p, i) =>
                  p === '...'
                    ? <span key={`dots2-${i}`} className="px-2 text-gray-400">...</span>
                    : <button
                        key={p}
                        onClick={() => goToPage(p as number)}
                        className={`w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition ${
                          currentPage === p ? 'bg-blue-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50'
                        }`}
                      >{p}</button>
                )}
                <button
                  disabled={currentPage >= lastPage}
                  onClick={() => goToPage(currentPage + 1)}
                  className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                  Siguiente
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
