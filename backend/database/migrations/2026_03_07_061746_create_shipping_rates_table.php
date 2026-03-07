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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('zona_id')->constrained('shipping_zones')->onDelete('cascade');
            $table->decimal('precio_base', 10, 2)->default(0);
            $table->decimal('precio_por_km', 8, 2)->default(0);
            $table->decimal('precio_por_kg', 8, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['vehiculo_id', 'zona_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
