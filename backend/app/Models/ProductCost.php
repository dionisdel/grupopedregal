<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCost extends Model
{
    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'precio_compra',
        'descuento_1',
        'descuento_2',
        'descuento_3',
        'coste_transporte_unitario',
        'unidad_id',
        'fecha_vigencia_desde',
        'fecha_vigencia_hasta',
        'moneda',
        'activo',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:4',
        'descuento_1' => 'decimal:4',
        'descuento_2' => 'decimal:4',
        'descuento_3' => 'decimal:4',
        'precio_neto' => 'decimal:4',
        'coste_transporte_unitario' => 'decimal:4',
        'precio_coste_final' => 'decimal:4',
        'fecha_vigencia_desde' => 'date',
        'fecha_vigencia_hasta' => 'date',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'proveedor_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unidad_id');
    }
}
