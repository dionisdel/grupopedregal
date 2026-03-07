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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('tipo', ['camion', 'camion_grua', 'trailer']);
            $table->decimal('capacidad_kg', 10, 2);
            $table->decimal('capacidad_m3', 10, 2)->nullable();
            $table->decimal('coste_km', 8, 2)->default(0);
            $table->decimal('coste_fijo_salida', 8, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
