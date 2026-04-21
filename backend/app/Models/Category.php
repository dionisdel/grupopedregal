<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'parent_id',
        'codigo',
        'nombre',
        'slug',
        'descripcion',
        'descripcion_web',
        'imagen_url',
        'orden',
        'nivel',
        'cuenta_contable_compra',
        'cuenta_contable_venta',
        'activo',
    ];

    protected $casts = [
        'nivel' => 'integer',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('orden');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Product::class, 'categoria_id');
    }
}
