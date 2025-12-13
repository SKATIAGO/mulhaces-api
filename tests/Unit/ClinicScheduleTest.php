<?php

namespace Tests\Unit;

use App\Domain\Schedule\ClinicSchedule;
use PHPUnit\Framework\TestCase;

/**
 * ClinicScheduleTest - Tests unitarios para la lógica de scheduling
 * 
 * Estos tests prueban el algoritmo de detección de conflictos de forma aislada,
 * sin dependencias de Laravel (base de datos, etc.)
 */
class ClinicScheduleTest extends TestCase
{
    private ClinicSchedule $scheduler;

    /**
     * setUp() - Se ejecuta antes de cada test
     * 
     * Crea una instancia fresca de ClinicSchedule para cada test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new ClinicSchedule();
    }

    /**
     * Test: Slot disponible cuando no hay citas existentes
     */
    public function test_slot_is_available_when_no_existing_appointments(): void
    {
        $start = new \DateTime('2025-12-13 10:00:00');
        $end = new \DateTime('2025-12-13 11:00:00');

        $result = $this->scheduler->isSlotAvailable([], $start, $end);

        $this->assertTrue($result, 'El slot debería estar disponible sin citas existentes');
    }

    /**
     * Test: Detecta conflicto cuando nueva cita empieza durante una existente
     * 
     * Existente: 10:00 - 11:00
     * Nueva:            10:30 - 11:30  ❌ CONFLICTO
     */
    public function test_detects_conflict_when_new_appointment_starts_during_existing(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 10:00:00',
                'end_time' => '2025-12-13 11:00:00',
            ],
        ];

        $newStart = new \DateTime('2025-12-13 10:30:00');
        $newEnd = new \DateTime('2025-12-13 11:30:00');

        $result = $this->scheduler->isSlotAvailable($existingAppointments, $newStart, $newEnd);

        $this->assertFalse($result, 'Debe detectar conflicto cuando nueva cita empieza durante una existente');
    }

    /**
     * Test: Detecta conflicto cuando nueva cita termina durante una existente
     * 
     * Existente:       10:00 - 11:00
     * Nueva:     09:30 - 10:30  ❌ CONFLICTO
     */
    public function test_detects_conflict_when_new_appointment_ends_during_existing(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 10:00:00',
                'end_time' => '2025-12-13 11:00:00',
            ],
        ];

        $newStart = new \DateTime('2025-12-13 09:30:00');
        $newEnd = new \DateTime('2025-12-13 10:30:00');

        $result = $this->scheduler->isSlotAvailable($existingAppointments, $newStart, $newEnd);

        $this->assertFalse($result, 'Debe detectar conflicto cuando nueva cita termina durante una existente');
    }

    /**
     * Test: Detecta conflicto cuando nueva cita envuelve completamente a una existente
     * 
     * Existente:   10:00 - 10:30
     * Nueva:     09:00 - 12:00  ❌ CONFLICTO
     */
    public function test_detects_conflict_when_new_appointment_wraps_existing(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 10:00:00',
                'end_time' => '2025-12-13 10:30:00',
            ],
        ];

        $newStart = new \DateTime('2025-12-13 09:00:00');
        $newEnd = new \DateTime('2025-12-13 12:00:00');

        $result = $this->scheduler->isSlotAvailable($existingAppointments, $newStart, $newEnd);

        $this->assertFalse($result, 'Debe detectar conflicto cuando nueva cita envuelve a una existente');
    }

    /**
     * Test: Citas consecutivas NO tienen conflicto
     * 
     * Existente: 10:00 - 11:00
     * Nueva:                11:00 - 12:00  ✅ OK
     */
    public function test_consecutive_appointments_do_not_conflict(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 10:00:00',
                'end_time' => '2025-12-13 11:00:00',
            ],
        ];

        $newStart = new \DateTime('2025-12-13 11:00:00');
        $newEnd = new \DateTime('2025-12-13 12:00:00');

        $result = $this->scheduler->isSlotAvailable($existingAppointments, $newStart, $newEnd);

        $this->assertTrue($result, 'Citas consecutivas (sin solapamiento) no deben tener conflicto');
    }

    /**
     * Test: Slot disponible antes de citas existentes
     * 
     * Nueva:     08:00 - 09:00  ✅
     * Existente:        10:00 - 11:00
     */
    public function test_slot_is_available_before_existing_appointments(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 10:00:00',
                'end_time' => '2025-12-13 11:00:00',
            ],
        ];

        $newStart = new \DateTime('2025-12-13 08:00:00');
        $newEnd = new \DateTime('2025-12-13 09:00:00');

        $result = $this->scheduler->isSlotAvailable($existingAppointments, $newStart, $newEnd);

        $this->assertTrue($result, 'Slot antes de citas existentes debe estar disponible');
    }

    /**
     * Test: Slot disponible después de citas existentes
     * 
     * Existente: 10:00 - 11:00
     * Nueva:                    12:00 - 13:00  ✅
     */
    public function test_slot_is_available_after_existing_appointments(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 10:00:00',
                'end_time' => '2025-12-13 11:00:00',
            ],
        ];

        $newStart = new \DateTime('2025-12-13 12:00:00');
        $newEnd = new \DateTime('2025-12-13 13:00:00');

        $result = $this->scheduler->isSlotAvailable($existingAppointments, $newStart, $newEnd);

        $this->assertTrue($result, 'Slot después de citas existentes debe estar disponible');
    }

    /**
     * Test: Detecta conflicto con múltiples citas existentes
     */
    public function test_detects_conflict_with_multiple_existing_appointments(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 09:00:00',
                'end_time' => '2025-12-13 10:00:00',
            ],
            [
                'start_time' => '2025-12-13 11:00:00',
                'end_time' => '2025-12-13 12:00:00',
            ],
            [
                'start_time' => '2025-12-13 14:00:00',
                'end_time' => '2025-12-13 15:00:00',
            ],
        ];

        // Conflicto con la segunda cita
        $newStart = new \DateTime('2025-12-13 11:30:00');
        $newEnd = new \DateTime('2025-12-13 12:30:00');

        $result = $this->scheduler->isSlotAvailable($existingAppointments, $newStart, $newEnd);

        $this->assertFalse($result, 'Debe detectar conflicto entre múltiples citas');
    }

    /**
     * Test: Slot inválido (fin antes del inicio)
     */
    public function test_invalid_slot_when_end_before_start(): void
    {
        $start = new \DateTime('2025-12-13 11:00:00');
        $end = new \DateTime('2025-12-13 10:00:00'); // Termina antes de empezar

        $result = $this->scheduler->isSlotAvailable([], $start, $end);

        $this->assertFalse($result, 'Slot con end_time antes de start_time debe ser inválido');
    }

    /**
     * Test: Slot inválido (duración cero)
     */
    public function test_invalid_slot_with_zero_duration(): void
    {
        $start = new \DateTime('2025-12-13 10:00:00');
        $end = new \DateTime('2025-12-13 10:00:00'); // Misma hora

        $result = $this->scheduler->isSlotAvailable([], $start, $end);

        $this->assertFalse($result, 'Slot con duración cero debe ser inválido');
    }

    /**
     * Test: findAvailableSlots encuentra slots disponibles correctamente
     */
    public function test_find_available_slots_returns_correct_slots(): void
    {
        $existingAppointments = [
            [
                'start_time' => '2025-12-13 10:00:00',
                'end_time' => '2025-12-13 10:30:00',
            ],
            [
                'start_time' => '2025-12-13 11:00:00',
                'end_time' => '2025-12-13 11:30:00',
            ],
        ];

        $rangeStart = new \DateTime('2025-12-13 09:00:00');
        $rangeEnd = new \DateTime('2025-12-13 12:00:00');

        $availableSlots = $this->scheduler->findAvailableSlots(
            $existingAppointments,
            $rangeStart,
            $rangeEnd,
            30
        );

        // Debería encontrar slots: 09:00-09:30, 09:30-10:00, 10:30-11:00, 11:30-12:00
        $this->assertCount(4, $availableSlots, 'Debería encontrar 4 slots disponibles de 30 minutos');
        
        // Verificar el primer slot
        $this->assertEquals('2025-12-13 09:00:00', $availableSlots[0]['start']);
        $this->assertEquals('2025-12-13 09:30:00', $availableSlots[0]['end']);
    }
}
