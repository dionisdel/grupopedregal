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
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->unique()->constrained('products')->onDelete('cascade');
            $table->decimal('peso_kg', 10, 3)->nullable();
            $table->decimal('largo_cm', 10, 2)->nullable();
            $table->decimal('ancho_cm', 10, 2)->nullable();
            $table->decimal('alto_cm', 10, 2)->nullable();
            $table->decimal('volumen_m3', 10, 4)->nullable();
            $table->decimal('metros_por_unidad', 10, 3)->nullable();
            $table->decimal('m2_por_unidad', 10, 4)->nullable();
            $table->integer('unidades_por_embalaje')->nullable();
            $table->integer('embalajes_por_palet')->nullable();
            $table->integer('unidades_por_palet')->nullable();
            $table->boolean('palet_retornable')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specs');
    }
};
