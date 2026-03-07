<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'nombre',
        'tipo',
        'capacidad_kg',
        'capacidad_m3',
        'coste_km',
        'coste_fijo_salida',
        'activo',
    ];

    protected $casts = [
        'capacidad_kg' => 'decimal:2',
        'capacidad_m3' => 'decimal:2',
        'coste_km' => 'decimal:2',
        'coste_fijo_salida' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
