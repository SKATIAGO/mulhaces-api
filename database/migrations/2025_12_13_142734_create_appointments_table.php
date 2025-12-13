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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); // Primary key
            
            // FOREIGN KEYS - Relaciones con otras tablas
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            // foreignId crea BIGINT UNSIGNED
            // constrained() crea FK a tabla 'patients' (nombre en plural)
            // onDelete('cascade') = si borras patient, borra sus appointments
            
            $table->foreignId('treatment_id')->constrained()->onDelete('cascade');
            // Relación con treatments
            
            // CAMPOS DE LA CITA
            $table->dateTime('start_time'); // Fecha y hora de inicio
            $table->dateTime('end_time'); // Fecha y hora de fin (calculado: start + duration)
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled'])
                  ->default('scheduled'); // Estado de la cita
            $table->text('notes')->nullable(); // Notas del dentista/paciente
            
            $table->timestamps(); // created_at, updated_at
            
            // ÍNDICES para optimizar búsquedas
            $table->index('start_time'); // Buscar por fecha/hora rápidamente
            $table->index(['patient_id', 'start_time']); // Citas de un paciente ordenadas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
