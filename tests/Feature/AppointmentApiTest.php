<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AppointmentApiTest - Tests de integración para la API de citas
 * 
 * Usa RefreshDatabase para tener una base de datos limpia en cada test
 */
class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Puede listar citas
     */
    public function test_can_list_appointments(): void
    {
        // Crear datos de prueba
        $patient = Patient::factory()->create();
        $treatment = Treatment::factory()->create();
        Appointment::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
        ]);

        // Hacer request a la API
        $response = $this->getJson('/api/appointments');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data'); // 3 citas en la respuesta paginada
    }

    /**
     * Test: Puede crear una cita válida
     */
    public function test_can_create_appointment(): void
    {
        $patient = Patient::factory()->create();
        $treatment = Treatment::factory()->create([
            'duration_minutes' => 30,
        ]);

        $appointmentData = [
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
            'start_time' => '2025-12-20 10:00:00',
            'status' => 'scheduled',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201)
                 ->assertJsonPath('patient_id', $patient->id)
                 ->assertJsonPath('treatment_id', $treatment->id);

        // Verificar que se guardó en la base de datos
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
        ]);
    }

    /**
     * Test: No permite crear cita en slot ocupado
     */
    public function test_cannot_create_appointment_in_occupied_slot(): void
    {
        $patient = Patient::factory()->create();
        $treatment = Treatment::factory()->create();

        // Crear cita existente
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
            'start_time' => '2025-12-20 10:00:00',
            'end_time' => '2025-12-20 11:00:00',
            'status' => 'scheduled',
        ]);

        // Intentar crear cita que se solapa
        $appointmentData = [
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
            'start_time' => '2025-12-20 10:30:00',
            'end_time' => '2025-12-20 11:30:00',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(409); // Conflict
    }

    /**
     * Test: Validación de campos requeridos
     */
    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/appointments', []);

        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJsonValidationErrors(['patient_id', 'treatment_id', 'start_time']);
    }

    /**
     * Test: Puede ver detalles de una cita
     */
    public function test_can_show_appointment(): void
    {
        $patient = Patient::factory()->create(['name' => 'Juan Pérez']);
        $treatment = Treatment::factory()->create(['name' => 'Limpieza']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
        ]);

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('id', $appointment->id)
                 ->assertJsonPath('patient.name', 'Juan Pérez')
                 ->assertJsonPath('treatment.name', 'Limpieza');
    }

    /**
     * Test: Puede actualizar una cita
     */
    public function test_can_update_appointment(): void
    {
        $patient = Patient::factory()->create();
        $treatment = Treatment::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
            'status' => 'scheduled',
        ]);

        $response = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Test: Puede eliminar una cita
     */
    public function test_can_delete_appointment(): void
    {
        $patient = Patient::factory()->create();
        $treatment = Treatment::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
        ]);

        $response = $this->deleteJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
        ]);
    }

    /**
     * Test: Devuelve 404 para cita inexistente
     */
    public function test_returns_404_for_nonexistent_appointment(): void
    {
        $response = $this->getJson('/api/appointments/999999');

        $response->assertStatus(404);
    }

    /**
     * Test: Puede filtrar citas por fecha
     */
    public function test_can_filter_appointments_by_date(): void
    {
        $patient = Patient::factory()->create();
        $treatment = Treatment::factory()->create();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
            'start_time' => '2025-12-20 10:00:00',
        ]);

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'treatment_id' => $treatment->id,
            'start_time' => '2025-12-21 10:00:00',
        ]);

        $response = $this->getJson('/api/appointments?date=2025-12-20');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Test: Endpoint de available-slots funciona
     */
    public function test_available_slots_endpoint_works(): void
    {
        $response = $this->getJson('/api/appointments-available-slots?date=2025-12-20&duration=30');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'date',
                     'duration_minutes',
                     'available_slots',
                     'total_available',
                 ]);
    }
}
