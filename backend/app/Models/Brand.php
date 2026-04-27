<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'logo_url',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'marca_id');
    }
}
