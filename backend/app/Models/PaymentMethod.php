<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'nombre',
        'dias_plazo',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'dias_plazo' => 'integer',
        'activo' => 'boolean',
    ];
}
