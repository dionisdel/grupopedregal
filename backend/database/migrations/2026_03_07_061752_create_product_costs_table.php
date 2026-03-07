<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('suppliers')->onDelete('cascade');
            $table->decimal('precio_compra', 12, 4);
            $table->decimal('descuento_1', 5, 4)->default(0);
            $table->decimal('descuento_2', 5, 4)->default(0);
            $table->decimal('descuento_3', 5, 4)->default(0);
            $table->decimal('precio_neto', 12, 4)->storedAs('precio_compra * (1 - descuento_1) * (1 - descuento_2) * (1 - descuento_3)');
            $table->decimal('coste_transporte_unitario', 10, 4)->default(0);
            $table->decimal('precio_coste_final', 12, 4)->storedAs('precio_neto + coste_transporte_unitario');
            $table->foreignId('unidad_id')->constrained('units')->onDelete('restrict');
            $table->date('fecha_vigencia_desde');
            $table->date('fecha_vigencia_hasta')->nullable();
            $table->string('moneda', 3)->default('EUR');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['producto_id', 'proveedor_id', 'fecha_vigencia_desde'], 'idx_costs_prod_prov_fecha');
            $table->index(['activo', 'fecha_vigencia_desde'], 'idx_costs_activo_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_costs');
    }
};
