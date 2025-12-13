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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name'); // Nombre del tratamiento: "Limpieza", "Ortodoncia", etc.
            $table->text('description')->nullable(); // Descripción detallada (TEXT permite más caracteres)
            $table->decimal('price', 10, 2); // DECIMAL(10,2) - Precio: hasta 99,999,999.99
            $table->integer('duration_minutes')->default(30); // Duración en minutos (default 30)
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
