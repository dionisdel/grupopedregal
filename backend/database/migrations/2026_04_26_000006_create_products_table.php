<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create products table with all fixed, editable price, and calculated fields.
     * Requirements: 3.1, 3.2, 4.1
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Identity & description
            $table->id();
            $table->string('codigo_articulo', 40)->unique();
            $table->string('descripcion', 500);
            $table->string('slug', 500)->unique();

            // Relationships
            $table->foreignId('categoria_id')->constrained('categories');
            $table->foreignId('proveedor_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('codigo_proveedor', 40)->nullable();
            $table->string('codigo_articulo_proveedor', 40)->nullable();
            $table->foreignId('marca_id')->nullable()->constrained('brands')->nullOnDelete();

            // Physical specs
            $table->decimal('kg_litro', 10, 3)->nullable();
            $table->decimal('largo', 10, 3)->nullable();
            $table->decimal('ancho', 10, 3)->nullable();
            $table->decimal('metros_articulo', 10, 4)->nullable();
            $table->integer('unidades_por_articulo')->nullable();
            $table->integer('articulos_por_embalaje')->nullable();
            $table->integer('unidades_palet')->nullable();
            $table->boolean('palet_retornable')->default(false);

            // Editable prices
            $table->decimal('pvp_proveedor', 12, 4)->default(0);
            $table->decimal('desc_prov_1', 5, 2)->default(0);
            $table->decimal('coste_transporte', 12, 4)->default(0);
            $table->decimal('desc_camion_vip', 5, 2)->default(0);
            $table->decimal('desc_camion', 5, 2)->default(0);
            $table->decimal('desc_oferta', 5, 2)->default(0);
            $table->decimal('desc_vip', 5, 2)->default(0);
            $table->decimal('desc_empresas', 5, 2)->default(0);
            $table->decimal('desc_empresas_a', 5, 2)->default(0);
            $table->decimal('iva_porcentaje', 5, 2)->default(21);

            // Calculated prices (stored)
            $table->decimal('coste_neto', 12, 4)->default(0);
            $table->decimal('coste_neto_m2', 12, 4)->nullable();
            $table->decimal('coste_m2_trans', 12, 4)->nullable();
            $table->decimal('pre_pvp', 12, 4)->default(0);
            $table->decimal('pvp', 12, 4)->default(0);
            $table->decimal('neto_camion_vip', 12, 4)->default(0);
            $table->decimal('neto_camion', 12, 4)->default(0);
            $table->decimal('neto_oferta', 12, 4)->default(0);
            $table->decimal('neto_vip', 12, 4)->default(0);
            $table->decimal('neto_empresas', 12, 4)->default(0);
            $table->decimal('neto_empresas_a', 12, 4)->default(0);

            // Filters & state
            $table->json('filtros_dinamicos')->nullable();
            $table->string('imagen_url', 500)->nullable();
            $table->boolean('estado_publicado')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
