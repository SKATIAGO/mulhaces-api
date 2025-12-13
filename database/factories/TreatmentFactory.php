<?php

namespace Database\Factories;

use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * TreatmentFactory - Genera datos de prueba para tratamientos
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Treatment>
 */
class TreatmentFactory extends Factory
{
    protected $model = Treatment::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        // Tratamientos dentales comunes
        $treatments = [
            ['name' => 'Limpieza Dental', 'duration' => 30, 'price' => [50, 80]],
            ['name' => 'Empaste', 'duration' => 45, 'price' => [100, 150]],
            ['name' => 'Extracción', 'duration' => 60, 'price' => [80, 120]],
            ['name' => 'Endodoncia', 'duration' => 90, 'price' => [300, 500]],
            ['name' => 'Corona Dental', 'duration' => 120, 'price' => [400, 700]],
            ['name' => 'Blanqueamiento', 'duration' => 60, 'price' => [200, 350]],
            ['name' => 'Ortodoncia - Consulta', 'duration' => 30, 'price' => [50, 100]],
        ];

        $treatment = $this->faker->randomElement($treatments);

        return [
            'name' => $treatment['name'],
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomFloat(2, $treatment['price'][0], $treatment['price'][1]),
            'duration_minutes' => $treatment['duration'],
        ];
    }
}
