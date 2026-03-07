<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'codigo_postal_desde',
        'codigo_postal_hasta',
        'provincia',
        'distancia_km_aproximada',
        'activo',
    ];

    protected $casts = [
        'distancia_km_aproximada' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
