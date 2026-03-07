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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 150);
            $table->string('slug', 150)->unique();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_web')->nullable();
            $table->string('imagen_url')->nullable();
            $table->integer('orden')->default(0);
            $table->tinyInteger('nivel')->default(1);
            $table->string('cuenta_contable_compra', 20)->nullable();
            $table->string('cuenta_contable_venta', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['parent_id', 'orden']);
            $table->index('nivel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
