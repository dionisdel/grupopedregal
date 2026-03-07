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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre_comercial', 150);
            $table->string('razon_social', 150);
            $table->string('nif_cif', 20)->unique();
            $table->text('direccion')->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('pais', 100)->default('España');
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('iban', 34)->nullable();
            $table->foreignId('forma_pago_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('descuento_1', 5, 4)->default(0);
            $table->decimal('descuento_2', 5, 4)->default(0);
            $table->decimal('descuento_3', 5, 4)->default(0);
            $table->boolean('portes_incluidos')->default(false);
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->index('codigo');
            $table->index('nombre_comercial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
