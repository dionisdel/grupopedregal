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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('tipo_cliente_id')->constrained('customer_types')->onDelete('cascade');
            $table->decimal('precio_base', 12, 4);
            $table->decimal('descuento_porcentaje', 5, 4)->default(0);
            $table->decimal('precio_neto', 12, 4)->storedAs('precio_base * (1 - descuento_porcentaje)');
            $table->decimal('margen_porcentaje', 5, 4)->nullable();
            $table->decimal('margen_absoluto', 12, 4)->nullable();
            $table->date('fecha_vigencia_desde');
            $table->date('fecha_vigencia_hasta')->nullable();
            $table->tinyInteger('mes_tarifa')->nullable();
            $table->smallInteger('año_tarifa')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['producto_id', 'tipo_cliente_id', 'fecha_vigencia_desde'], 'unique_price');
            $table->index(['producto_id', 'tipo_cliente_id', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
