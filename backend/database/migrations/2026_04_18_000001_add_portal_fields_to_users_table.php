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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('role_id');
            $table->string('telefono', 20)->nullable()->after('customer_id');
            $table->string('empresa', 255)->nullable()->after('telefono');
            $table->string('nif_cif', 20)->nullable()->after('empresa');
            $table->enum('estado', ['activo', 'pendiente', 'inactivo'])->default('activo')->after('nif_cif');

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'telefono', 'empresa', 'nif_cif', 'estado']);
        });
    }
};
