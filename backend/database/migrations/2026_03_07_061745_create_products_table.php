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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->text('descripcion_corta_web')->nullable();
            $table->text('descripcion_larga_web')->nullable();
            $table->string('slug', 200)->unique();
            $table->foreignId('marca_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('categoria_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('proveedor_principal_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('unidad_base_id')->constrained('units')->onDelete('restrict');
            $table->foreignId('unidad_compra_id')->nullable()->constrained('units')->onDelete('restrict');
            $table->boolean('activo')->default(true);
            $table->boolean('visible_web')->default(false);
            $table->boolean('destacado')->default(false);
            $table->string('imagen_principal_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('sku');
            $table->index('nombre');
            $table->index('categoria_id');
            $table->index(['activo', 'visible_web']);
            $table->fullText(['nombre', 'descripcion']);
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
