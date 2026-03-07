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
        Schema::create('product_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->enum('tipo', ['proveedor', 'ean13', 'ean8', 'interno_antiguo', 'competencia']);
            $table->string('codigo', 100);
            $table->foreignId('proveedor_id')->nullable()->constrained('suppliers')->onDelete('cascade');
            $table->boolean('principal')->default(false);
            $table->timestamps();
            
            $table->index(['producto_id', 'tipo']);
            $table->index('codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_codes');
    }
};
