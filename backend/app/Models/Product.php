<?php

namespace App\Models;

use App\Services\PriceCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Editable price fields tracked for price history.
     */
    public const EDITABLE_PRICE_FIELDS = [
        'pvp_proveedor',
        'desc_prov_1',
        'coste_transporte',
        'desc_camion_vip',
        'desc_camion',
        'desc_oferta',
        'desc_vip',
        'desc_empresas',
        'desc_empresas_a',
        'iva_porcentaje',
    ];

    protected static function booted(): void
    {
        // Auto-calculate all derived price fields before saving
        static::saving(function (Product $product) {
            $calculated = PriceCalculatorService::calculate([
                'pvp_proveedor'   => $product->pvp_proveedor,
                'desc_prov_1'     => $product->desc_prov_1,
                'coste_transporte'=> $product->coste_transporte,
                'iva_porcentaje'  => $product->iva_porcentaje,
                'desc_camion_vip' => $product->desc_camion_vip,
                'desc_camion'     => $product->desc_camion,
                'desc_oferta'     => $product->desc_oferta,
                'desc_vip'        => $product->desc_vip,
                'desc_empresas'   => $product->desc_empresas,
                'desc_empresas_a' => $product->desc_empresas_a,
                'metros_articulo' => $product->metros_articulo,
            ]);

            foreach ($calculated as $field => $value) {
                $product->{$field} = $value;
            }
        });

        // Track editable price field changes for existing products
        static::saved(function (Product $product) {
            // Only track changes for existing products (not on first create)
            if ($product->wasRecentlyCreated) {
                return;
            }

            $changedBy = auth()->id();
            $now = now();

            foreach (self::EDITABLE_PRICE_FIELDS as $field) {
                $original = $product->getOriginal($field);
                $current = $product->{$field};

                // Compare as floats to detect actual changes
                if ((float) $original !== (float) $current) {
                    ProductPriceHistory::create([
                        'product_id'    => $product->id,
                        'field_changed' => $field,
                        'old_value'     => $original,
                        'new_value'     => $current,
                        'changed_at'    => $now,
                        'changed_by'    => $changedBy,
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        // Identity
        'codigo_articulo',
        'descripcion',
        'slug',
        // Relationships
        'categoria_id',
        'proveedor_id',
        'codigo_proveedor',
        'codigo_articulo_proveedor',
        'marca_id',
        // Physical specs
        'kg_litro',
        'largo',
        'ancho',
        'metros_articulo',
        'unidades_por_articulo',
        'articulos_por_embalaje',
        'unidades_palet',
        'palet_retornable',
        // Editable prices
        'pvp_proveedor',
        'desc_prov_1',
        'coste_transporte',
        'desc_camion_vip',
        'desc_camion',
        'desc_oferta',
        'desc_vip',
        'desc_empresas',
        'desc_empresas_a',
        'iva_porcentaje',
        // Filters & state
        'filtros_dinamicos',
        'imagen_url',
        'estado_publicado',
    ];

    protected $casts = [
        'filtros_dinamicos' => 'array',
        'estado_publicado' => 'boolean',
        'palet_retornable' => 'boolean',
        'kg_litro' => 'float',
        'largo' => 'float',
        'ancho' => 'float',
        'metros_articulo' => 'float',
        'pvp_proveedor' => 'float',
        'desc_prov_1' => 'float',
        'coste_transporte' => 'float',
        'desc_camion_vip' => 'float',
        'desc_camion' => 'float',
        'desc_oferta' => 'float',
        'desc_vip' => 'float',
        'desc_empresas' => 'float',
        'desc_empresas_a' => 'float',
        'iva_porcentaje' => 'float',
        'coste_neto' => 'float',
        'coste_neto_m2' => 'float',
        'coste_m2_trans' => 'float',
        'pre_pvp' => 'float',
        'pvp' => 'float',
        'neto_camion_vip' => 'float',
        'neto_camion' => 'float',
        'neto_oferta' => 'float',
        'neto_vip' => 'float',
        'neto_empresas' => 'float',
        'neto_empresas_a' => 'float',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'proveedor_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'marca_id');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_products')
            ->withPivot('stock_quantity')
            ->withTimestamps();
    }
}
