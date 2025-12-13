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
        Schema::create('patients', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (BIGINT UNSIGNED)
            $table->string('name'); // VARCHAR(255) - Nombre del paciente
            $table->string('email')->unique(); // Email único para contacto
            $table->string('phone')->nullable(); // Teléfono opcional
            $table->date('date_of_birth')->nullable(); // Fecha de nacimiento
            $table->timestamps(); // created_at y updated_at (automáticos)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
