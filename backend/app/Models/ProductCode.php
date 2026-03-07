<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCode extends Model
{
    protected $fillable = [
        'producto_id',
        'tipo',
        'codigo',
        'proveedor_id',
        'principal',
    ];

    protected $casts = [
        'principal' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'proveedor_id');
    }
}
