<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * AppointmentFactory - Genera datos de prueba para citas
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        // Generar fecha/hora de inicio entre hoy y 30 días en el futuro
        // Durante horario de oficina (9:00 - 17:00)
        $startTime = $this->faker->dateTimeBetween('now', '+30 days');
        $startTime->setTime(
            $this->faker->numberBetween(9, 16), // Hora entre 9 y 16
            $this->faker->randomElement([0, 30]) // Minutos: 0 o 30
        );

        // Duración aleatoria entre 30 y 120 minutos
        $durationMinutes = $this->faker->randomElement([30, 45, 60, 90, 120]);
        
        $endTime = clone $startTime;
        $endTime->modify("+{$durationMinutes} minutes");

        return [
            'patient_id' => Patient::factory(),
            'treatment_id' => Treatment::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $this->faker->randomElement(['scheduled', 'confirmed', 'completed', 'cancelled']),
            'notes' => $this->faker->optional(0.3)->sentence(), // 30% tienen notas
        ];
    }
}
