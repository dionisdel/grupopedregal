<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre_comercial',
        'razon_social',
        'nif_cif',
        'direccion',
        'codigo_postal',
        'ciudad',
        'provincia',
        'pais',
        'telefono',
        'email',
        'iban',
        'forma_pago_id',
        'descuento_1',
        'descuento_2',
        'descuento_3',
        'portes_incluidos',
        'activo',
        'notas',
    ];

    protected $casts = [
        'descuento_1' => 'decimal:4',
        'descuento_2' => 'decimal:4',
        'descuento_3' => 'decimal:4',
        'portes_incluidos' => 'boolean',
        'activo' => 'boolean',
    ];

    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'forma_pago_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Product::class, 'proveedor_principal_id');
    }

    public function costes(): HasMany
    {
        return $this->hasMany(ProductCost::class, 'proveedor_id');
    }
}
