<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            // Cambiar margen_porcentaje de decimal(5,4) a decimal(6,2)
            // Esto permite valores de 0.00 a 9999.99
            $table->decimal('margen_porcentaje', 6, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->decimal('margen_porcentaje', 5, 4)->nullable()->change();
        });
    }
};
