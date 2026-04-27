"use client";

import { useState, useEffect, useCallback } from "react";
import {
  ChevronRight,
  ChevronDown,
  Plus,
  Pencil,
  Trash2,
  ArrowUp,
  ArrowDown,
  FolderTree,
  X,
  Upload,
} from "lucide-react";
import type { CategoryNode } from "@/services/types";
import { adminService } from "@/services/admin.service";

interface CategoryTreeEditorProps {
  onSelectCategory?: (category: CategoryNode | null) => void;
  selectedCategoryId?: number | null;
}

interface CategoryFormData {
  nombre: string;
  slug: string;
  descripcion: string;
  parent_id: number | null;
  imagen_banner_url: string | null;
  imagen_thumbnail_url: string | null;
}

const emptyForm: CategoryFormData = {
  nombre: "",
  slug: "",
  descripcion: "",
  parent_id: null,
  imagen_banner_url: null,
  imagen_thumbnail_url: null,
};

export default function CategoryTreeEditor({
  onSelectCategory,
  selectedCategoryId,
}: CategoryTreeEditorProps) {
  const [categories, setCategories] = useState<CategoryNode[]>([]);
  const [expanded, setExpanded] = useState<Set<number>>(new Set());
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<CategoryFormData>(emptyForm);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadCategories = useCallback(async () => {
    setLoading(true);
    try {
      const data = await adminService.getCategories();
      setCategories(data);
    } catch {
      setError("Error al cargar categorías");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadCategories();
  }, [loadCategories]);

  const toggleExpand = (id: number) => {
    setExpanded((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const generateSlug = (name: string) =>
    name
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "");

  const openCreateModal = (parentId: number | null = null) => {
    setEditingId(null);
    setForm({ ...emptyForm, parent_id: parentId });
    setModalOpen(true);
    setError(null);
  };

  const openEditModal = (cat: CategoryNode) => {
    setEditingId(cat.id);
    setForm({
      nombre: cat.nombre,
      slug: cat.slug,
      descripcion: cat.descripcion || "",
      parent_id: cat.parent_id,
      imagen_banner_url: cat.imagen_banner_url,
      imagen_thumbnail_url: cat.imagen_thumbnail_url,
    });
    setModalOpen(true);
    setError(null);
  };

  const handleSave = async () => {
    if (!form.nombre.trim()) {
      setError("El nombre es obligatorio");
      return;
    }
    setSaving(true);
    setError(null);
    try {
      const payload = {
        ...form,
        slug: form.slug || generateSlug(form.nombre),
      };
      if (editingId) {
        await adminService.updateCategory(editingId, payload as Partial<CategoryNode>);
      } else {
        await adminService.createCategory(payload as Partial<CategoryNode>);
      }
      setModalOpen(false);
      await loadCategories();
    } catch (err: unknown) {
      const msg =
        err && typeof err === "object" && "response" in err
          ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
          : undefined;
      setError(msg || "Error al guardar");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("¿Eliminar esta categoría? Se eliminarán también sus subcategorías.")) return;
    try {
      await adminService.deleteCategory(id);
      if (selectedCategoryId === id) onSelectCategory?.(null);
      await loadCategories();
    } catch {
      setError("Error al eliminar categoría");
    }
  };

  const handleImageUpload = async (
    e: React.ChangeEvent<HTMLInputElement>,
    field: "imagen_banner_url" | "imagen_thumbnail_url"
  ) => {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      const { url } = await adminService.uploadImage(file, "category");
      setForm((prev) => ({ ...prev, [field]: url }));
    } catch {
      setError("Error al subir imagen");
    }
  };

  const handleReorder = async (
    siblings: CategoryNode[],
    index: number,
    direction: "up" | "down"
  ) => {
    const swapIndex = direction === "up" ? index - 1 : index + 1;
    if (swapIndex < 0 || swapIndex >= siblings.length) return;
    const reordered = [...siblings];
    [reordered[index], reordered[swapIndex]] = [reordered[swapIndex], reordered[index]];
    const order = reordered.map((cat, i) => ({
      id: cat.id,
      parent_id: cat.parent_id,
      orden: i,
    }));
    try {
      await adminService.reorderCategories(order);
      await loadCategories();
    } catch {
      setError("Error al reordenar");
    }
  };

  const flattenForRender = (
    nodes: CategoryNode[],
    depth: number = 0,
    parentSiblings?: CategoryNode[]
  ): { node: CategoryNode; depth: number; siblings: CategoryNode[]; index: number }[] => {
    const result: { node: CategoryNode; depth: number; siblings: CategoryNode[]; index: number }[] = [];
    const siblings = parentSiblings || nodes;
    nodes.forEach((node, index) => {
      result.push({ node, depth, siblings, index });
      if (expanded.has(node.id) && node.children?.length > 0) {
        result.push(...flattenForRender(node.children, depth + 1, node.children));
      }
    });
    return result;
  };

  const rows = flattenForRender(categories);

  return (
    <div className="flex flex-col h-full">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
        <div className="flex items-center gap-2">
          <FolderTree size={18} className="text-[#E8751A]" />
          <h2 className="text-sm font-semibold text-[#333]">Categorías</h2>
        </div>
        <button
          onClick={() => openCreateModal(null)}
          className="flex items-center gap-1 text-xs font-medium text-white bg-[#E8751A] px-3 py-1.5 rounded hover:opacity-90 transition-opacity"
        >
          <Plus size={14} />
          Nueva
        </button>
      </div>

      {/* Error */}
      {error && (
        <div className="mx-4 mt-2 text-xs text-red-600 bg-red-50 px-3 py-2 rounded">
          {error}
        </div>
      )}

      {/* Tree table */}
      <div className="flex-1 overflow-auto">
        {loading ? (
          <div className="flex items-center justify-center py-12 text-sm text-gray-400">
            Cargando...
          </div>
        ) : rows.length === 0 ? (
          <div className="flex items-center justify-center py-12 text-sm text-gray-400">
            Sin categorías
          </div>
        ) : (
          <table className="w-full text-sm">
            <tbody>
              {rows.map(({ node, depth, siblings, index }) => {
                const hasChildren = node.children && node.children.length > 0;
                const isSelected = selectedCategoryId === node.id;
                return (
                  <tr
                    key={node.id}
                    className={`border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors ${
                      isSelected ? "bg-orange-50" : ""
                    }`}
                    onClick={() => onSelectCategory?.(node)}
                  >
                    <td className="py-2 px-2" style={{ paddingLeft: `${depth * 20 + 8}px` }}>
                      <div className="flex items-center gap-1">
                        {hasChildren ? (
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              toggleExpand(node.id);
                            }}
                            className="p-0.5 hover:bg-gray-200 rounded"
                          >
                            {expanded.has(node.id) ? (
                              <ChevronDown size={14} />
                            ) : (
                              <ChevronRight size={14} />
                            )}
                          </button>
                        ) : (
                          <span className="w-5" />
                        )}
                        <span
                          className={`truncate ${isSelected ? "font-semibold text-[#E8751A]" : "text-[#333]"}`}
                          title={node.nombre}
                        >
                          {node.nombre}
                        </span>
                        <span className="text-xs text-gray-400 ml-1">
                          ({node.product_count})
                        </span>
                      </div>
                    </td>
                    <td className="py-2 px-1 w-28">
                      <div className="flex items-center gap-0.5" onClick={(e) => e.stopPropagation()}>
                        <button
                          onClick={() => handleReorder(siblings, index, "up")}
                          disabled={index === 0}
                          className="p-1 hover:bg-gray-200 rounded disabled:opacity-30"
                          title="Subir"
                        >
                          <ArrowUp size={12} />
                        </button>
                        <button
                          onClick={() => handleReorder(siblings, index, "down")}
                          disabled={index === siblings.length - 1}
                          className="p-1 hover:bg-gray-200 rounded disabled:opacity-30"
                          title="Bajar"
                        >
                          <ArrowDown size={12} />
                        </button>
                        <button
                          onClick={() => openCreateModal(node.id)}
                          className="p-1 hover:bg-gray-200 rounded text-green-600"
                          title="Añadir subcategoría"
                        >
                          <Plus size={12} />
                        </button>
                        <button
                          onClick={() => openEditModal(node)}
                          className="p-1 hover:bg-gray-200 rounded text-blue-600"
                          title="Editar"
                        >
                          <Pencil size={12} />
                        </button>
                        <button
                          onClick={() => handleDelete(node.id)}
                          className="p-1 hover:bg-gray-200 rounded text-red-500"
                          title="Eliminar"
                        >
                          <Trash2 size={12} />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </div>

      {/* Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <div className="flex items-center justify-between px-5 py-4 border-b">
              <h3 className="font-semibold text-[#333]">
                {editingId ? "Editar categoría" : "Nueva categoría"}
              </h3>
              <button onClick={() => setModalOpen(false)} className="p-1 hover:bg-gray-100 rounded">
                <X size={18} />
              </button>
            </div>
            <div className="px-5 py-4 flex flex-col gap-4">
              {error && (
                <div className="text-xs text-red-600 bg-red-50 px-3 py-2 rounded">{error}</div>
              )}
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                <input
                  type="text"
                  value={form.nombre}
                  onChange={(e) => {
                    const nombre = e.target.value;
                    setForm((prev) => ({
                      ...prev,
                      nombre,
                      slug: editingId ? prev.slug : generateSlug(nombre),
                    }));
                  }}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">Slug</label>
                <input
                  type="text"
                  value={form.slug}
                  onChange={(e) => setForm((prev) => ({ ...prev, slug: e.target.value }))}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                <textarea
                  value={form.descripcion}
                  onChange={(e) => setForm((prev) => ({ ...prev, descripcion: e.target.value }))}
                  rows={3}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E8751A]/40 resize-none"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-gray-600 mb-1">Banner</label>
                  <label className="flex items-center gap-1 text-xs text-[#E8751A] cursor-pointer hover:underline">
                    <Upload size={14} />
                    {form.imagen_banner_url ? "Cambiar" : "Subir"}
                    <input
                      type="file"
                      accept="image/*"
                      className="hidden"
                      onChange={(e) => handleImageUpload(e, "imagen_banner_url")}
                    />
                  </label>
                  {form.imagen_banner_url && (
                    <p className="text-[10px] text-gray-400 mt-1 truncate">{form.imagen_banner_url}</p>
                  )}
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-600 mb-1">Thumbnail</label>
                  <label className="flex items-center gap-1 text-xs text-[#E8751A] cursor-pointer hover:underline">
                    <Upload size={14} />
                    {form.imagen_thumbnail_url ? "Cambiar" : "Subir"}
                    <input
                      type="file"
                      accept="image/*"
                      className="hidden"
                      onChange={(e) => handleImageUpload(e, "imagen_thumbnail_url")}
                    />
                  </label>
                  {form.imagen_thumbnail_url && (
                    <p className="text-[10px] text-gray-400 mt-1 truncate">{form.imagen_thumbnail_url}</p>
                  )}
                </div>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-5 py-4 border-t">
              <button
                onClick={() => setModalOpen(false)}
                className="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
              >
                Cancelar
              </button>
              <button
                onClick={handleSave}
                disabled={saving}
                className="px-4 py-2 text-sm font-medium text-white bg-[#E8751A] rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
              >
                {saving ? "Guardando..." : "Guardar"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
