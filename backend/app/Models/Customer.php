<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
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
        'tipo_cliente_id',
        'zona_id',
        'descuento_adicional',
        'limite_credito',
        'activo',
        'notas',
    ];

    protected $casts = [
        'descuento_adicional' => 'decimal:4',
        'limite_credito' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'forma_pago_id');
    }

    public function tipoCliente(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'tipo_cliente_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zona_id');
    }
}
