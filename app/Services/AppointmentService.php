<?php

namespace App\Services;

use App\Domain\Schedule\ClinicSchedule;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * AppointmentService - Servicio de Laravel para gestión de citas
 * 
 * Este servicio actúa como puente entre Laravel (Controllers, Eloquent) 
 * y la lógica de negocio framework-agnostic (ClinicSchedule).
 * 
 * Responsabilidades:
 * - Validar disponibilidad antes de crear citas
 * - Calcular automáticamente end_time basado en duration del treatment
 * - Obtener slots disponibles para un rango de fechas
 */
class AppointmentService
{
    /**
     * @var ClinicSchedule Instancia de la lógica de scheduling
     */
    private ClinicSchedule $scheduler;

    /**
     * Constructor - Inyección de dependencias
     * 
     * Laravel automáticamente inyecta ClinicSchedule cuando se usa este servicio
     */
    public function __construct(ClinicSchedule $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * Verifica si un slot está disponible para una nueva cita
     *
     * @param Carbon $startTime Hora de inicio propuesta
     * @param Carbon $endTime Hora de fin propuesta
     * @param int|null $excludeAppointmentId ID de cita a excluir (útil para editar citas existentes)
     * @return bool
     */
    public function isSlotAvailable(Carbon $startTime, Carbon $endTime, ?int $excludeAppointmentId = null): bool
    {
        // Obtener todas las citas que podrían conflictuar
        // Solo necesitamos las que están en el mismo rango de tiempo
        $query = Appointment::query()
            ->where('status', '!=', 'cancelled') // Ignorar citas canceladas
            ->where(function ($q) use ($startTime, $endTime) {
                // Buscar citas que se solapen con el rango propuesto
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      // También buscar citas que envuelvan completamente el rango
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        // Si estamos editando una cita existente, excluirla de la búsqueda
        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        $existingAppointments = $query->get();

        // Convertir Eloquent Collection a array simple para ClinicSchedule
        $appointmentsArray = $existingAppointments->map(function ($appointment) {
            return [
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
            ];
        })->toArray();

        // Usar la lógica framework-agnostic para verificar disponibilidad
        return $this->scheduler->isSlotAvailable($appointmentsArray, $startTime, $endTime);
    }

    /**
     * Crear una nueva cita con validación automática de disponibilidad
     *
     * @param array $data Datos de la cita
     * @return Appointment
     * @throws \Exception Si el slot no está disponible
     */
    public function createAppointment(array $data): Appointment
    {
        $startTime = Carbon::parse($data['start_time']);
        
        // Si no se proporciona end_time, calcularlo desde el treatment
        if (!isset($data['end_time'])) {
            $treatment = \App\Models\Treatment::findOrFail($data['treatment_id']);
            $endTime = $startTime->copy()->addMinutes($treatment->duration_minutes);
            $data['end_time'] = $endTime;
        } else {
            $endTime = Carbon::parse($data['end_time']);
        }

        // Validar que el slot esté disponible
        if (!$this->isSlotAvailable($startTime, $endTime)) {
            throw new \Exception('El horario seleccionado no está disponible. Por favor, elige otro horario.');
        }

        // Crear la cita
        return Appointment::create($data);
    }

    /**
     * Actualizar una cita existente con validación de disponibilidad
     *
     * @param Appointment $appointment
     * @param array $data
     * @return Appointment
     * @throws \Exception Si el nuevo slot no está disponible
     */
    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        // Si se cambia el horario, validar disponibilidad
        if (isset($data['start_time']) || isset($data['end_time'])) {
            $startTime = Carbon::parse($data['start_time'] ?? $appointment->start_time);
            $endTime = Carbon::parse($data['end_time'] ?? $appointment->end_time);

            // Excluir la cita actual de la validación
            if (!$this->isSlotAvailable($startTime, $endTime, $appointment->id)) {
                throw new \Exception('El nuevo horario no está disponible.');
            }
        }

        $appointment->update($data);
        return $appointment;
    }

    /**
     * Obtener todos los slots disponibles para un día específico
     *
     * @param Carbon $date Día para buscar slots
     * @param int $slotDurationMinutes Duración de cada slot en minutos
     * @param string $startHour Hora de inicio (formato "H:i")
     * @param string $endHour Hora de fin (formato "H:i")
     * @return array
     */
    public function getAvailableSlots(
        Carbon $date,
        int $slotDurationMinutes = 30,
        string $startHour = '09:00',
        string $endHour = '18:00'
    ): array {
        // Definir el rango de horario de la clínica
        $rangeStart = $date->copy()->setTimeFromTimeString($startHour);
        $rangeEnd = $date->copy()->setTimeFromTimeString($endHour);

        // Obtener citas existentes del día
        $existingAppointments = Appointment::query()
            ->whereDate('start_time', $date)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(function ($appointment) {
                return [
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                ];
            })
            ->toArray();

        // Usar ClinicSchedule para encontrar slots disponibles
        return $this->scheduler->findAvailableSlots(
            $existingAppointments,
            $rangeStart,
            $rangeEnd,
            $slotDurationMinutes
        );
    }
}
