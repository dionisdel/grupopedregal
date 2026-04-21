<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'm2',
        'merma_porcentaje',
        'subtotal',
        'total',
        'resultado_json',
    ];

    protected $casts = [
        'm2' => 'decimal:2',
        'merma_porcentaje' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'resultado_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
