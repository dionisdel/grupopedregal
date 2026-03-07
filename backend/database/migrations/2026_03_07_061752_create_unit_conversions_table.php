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
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('unidad_origen_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('unidad_destino_id')->constrained('units')->onDelete('cascade');
            $table->decimal('factor_conversion', 12, 6);
            $table->text('formula')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['producto_id', 'unidad_origen_id', 'unidad_destino_id'], 'unique_conversion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
