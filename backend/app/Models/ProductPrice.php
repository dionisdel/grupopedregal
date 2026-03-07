<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'producto_id',
        'tipo_cliente_id',
        'precio_base',
        'descuento_porcentaje',
        'margen_porcentaje',
        'margen_absoluto',
        'fecha_vigencia_desde',
        'fecha_vigencia_hasta',
        'mes_tarifa',
        'año_tarifa',
        'activo',
    ];

    protected $casts = [
        'precio_base' => 'decimal:4',
        'descuento_porcentaje' => 'decimal:4',
        'precio_neto' => 'decimal:4',
        'margen_porcentaje' => 'decimal:4',
        'margen_absoluto' => 'decimal:4',
        'fecha_vigencia_desde' => 'date',
        'fecha_vigencia_hasta' => 'date',
        'mes_tarifa' => 'integer',
        'año_tarifa' => 'integer',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function tipoCliente(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'tipo_cliente_id');
    }
}
