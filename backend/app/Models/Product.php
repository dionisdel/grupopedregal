<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'nombre',
        'descripcion',
        'descripcion_corta_web',
        'descripcion_larga_web',
        'slug',
        'marca_id',
        'categoria_id',
        'proveedor_principal_id',
        'unidad_base_id',
        'unidad_compra_id',
        'activo',
        'visible_web',
        'destacado',
        'imagen_principal_url',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'visible_web' => 'boolean',
        'destacado' => 'boolean',
    ];

    // Relaciones
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'marca_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function proveedorPrincipal(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'proveedor_principal_id');
    }

    public function unidadBase(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unidad_base_id');
    }

    public function unidadCompra(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unidad_compra_id');
    }

    public function especificaciones(): HasOne
    {
        return $this->hasOne(ProductSpec::class, 'producto_id');
    }

    public function codigos(): HasMany
    {
        return $this->hasMany(ProductCode::class, 'producto_id');
    }

    public function costes(): HasMany
    {
        return $this->hasMany(ProductCost::class, 'producto_id');
    }

    public function precios(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'producto_id');
    }
}

