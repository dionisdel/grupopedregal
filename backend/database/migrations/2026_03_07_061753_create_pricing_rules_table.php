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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->enum('tipo', ['margen_fijo', 'margen_porcentual', 'precio_fijo', 'competencia']);
            $table->foreignId('categoria_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('producto_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('tipo_cliente_id')->nullable()->constrained('customer_types')->onDelete('cascade');
            $table->decimal('margen_porcentaje', 5, 4)->nullable();
            $table->decimal('margen_fijo', 10, 2)->nullable();
            $table->decimal('precio_minimo', 12, 4)->nullable();
            $table->integer('prioridad')->default(0);
            $table->date('fecha_vigencia_desde');
            $table->date('fecha_vigencia_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['categoria_id', 'tipo_cliente_id', 'prioridad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
