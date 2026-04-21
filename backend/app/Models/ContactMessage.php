<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'empresa',
        'linea_negocio',
        'asunto',
        'mensaje',
        'enviado',
    ];

    protected $casts = [
        'enviado' => 'boolean',
    ];
}
