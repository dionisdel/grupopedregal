<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpec extends Model
{
    use HasFactory;
    protected $fillable = [
        'producto_id',
        'peso_kg',
        'largo_cm',
        'ancho_cm',
        'alto_cm',
        'volumen_m3',
        'metros_por_unidad',
        'm2_por_unidad',
        'unidades_por_embalaje',
        'embalajes_por_palet',
        'unidades_por_palet',
        'palet_retornable',
    ];

    protected $casts = [
        'peso_kg' => 'decimal:3',
        'largo_cm' => 'decimal:2',
        'ancho_cm' => 'decimal:2',
        'alto_cm' => 'decimal:2',
        'volumen_m3' => 'decimal:4',
        'metros_por_unidad' => 'decimal:3',
        'm2_por_unidad' => 'decimal:4',
        'unidades_por_embalaje' => 'integer',
        'embalajes_por_palet' => 'integer',
        'unidades_por_palet' => 'integer',
        'palet_retornable' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
}
