<?php

namespace App\Domain\Schedule;

/**
 * ClinicSchedule - Framework-agnostic scheduling logic
 * 
 * Esta clase NO depende de Laravel, puede usarse en cualquier proyecto PHP.
 * Implementa la lógica de negocio para verificar disponibilidad de horarios.
 */
class ClinicSchedule
{
    /**
     * Check if a new time slot is available given an array of existing appointments.
     *
     * @param array $appointments Array of appointment arrays with 'start_time' and 'end_time' keys
     * @param \DateTimeInterface $start Inicio propuesto para la nueva cita
     * @param \DateTimeInterface $end Fin propuesto para la nueva cita
     * @return bool True si el slot está disponible, False si hay conflicto
     */
    public function isSlotAvailable(array $appointments, \DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        // Validación básica: el inicio debe ser antes del fin
        if ($start >= $end) {
            return false; // Slot inválido (tiempo negativo o cero)
        }

        // Iterar sobre todas las citas existentes para buscar conflictos
        foreach ($appointments as $appointment) {
            // Convertir start_time y end_time a DateTimeInterface
            // Esto permite que funcione con arrays, objetos o timestamps
            $appointmentStart = $this->toDateTime($appointment['start_time']);
            $appointmentEnd = $this->toDateTime($appointment['end_time']);

            // Algoritmo de detección de solapamiento:
            // Dos intervalos [A_start, A_end] y [B_start, B_end] se solapan SI:
            // A_start < B_end AND B_start < A_end
            //
            // Visualización:
            // Caso 1: Nueva cita empieza durante una existente
            //   Existente: |---------|
            //   Nueva:          |---------|  ❌ CONFLICTO
            //
            // Caso 2: Nueva cita termina durante una existente  
            //   Existente:     |---------|
            //   Nueva:     |---------|      ❌ CONFLICTO
            //
            // Caso 3: Nueva cita envuelve completamente a una existente
            //   Existente:   |-----|
            //   Nueva:     |-----------|    ❌ CONFLICTO
            //
            // Caso 4: Citas consecutivas (OK)
            //   Existente: |---------|
            //   Nueva:                |------|  ✅ OK (no se solapan)
            
            if ($start < $appointmentEnd && $appointmentStart < $end) {
                // ¡Conflicto detectado! El slot NO está disponible
                return false;
            }
        }

        // No se encontraron conflictos, el slot está disponible
        return true;
    }

    /**
     * Convierte varios tipos de datos a DateTimeInterface
     * 
     * Soporta: string, timestamp, DateTime, DateTimeImmutable
     *
     * @param mixed $value
     * @return \DateTimeInterface
     * @throws \InvalidArgumentException Si el formato no es válido
     */
    private function toDateTime($value): \DateTimeInterface
    {
        // Si ya es un objeto DateTime, devolverlo directamente
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        // Si es un string, intentar parsearlo
        if (is_string($value)) {
            $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
            
            // Si falla el formato exacto, intentar con strtotime (más flexible)
            if ($dateTime === false) {
                $dateTime = new \DateTimeImmutable($value);
            }
            
            return $dateTime;
        }

        // Si es un timestamp (integer)
        if (is_int($value)) {
            return (new \DateTimeImmutable())->setTimestamp($value);
        }

        throw new \InvalidArgumentException(
            'Value must be a DateTimeInterface, string, or timestamp'
        );
    }

    /**
     * Encuentra todos los slots disponibles en un rango de tiempo dado
     * 
     * Esta función es útil para mostrar al usuario los horarios disponibles
     *
     * @param array $appointments Citas existentes
     * @param \DateTimeInterface $rangeStart Inicio del rango a buscar
     * @param \DateTimeInterface $rangeEnd Fin del rango a buscar
     * @param int $slotDurationMinutes Duración de cada slot en minutos
     * @return array Array de slots disponibles con 'start' y 'end'
     */
    public function findAvailableSlots(
        array $appointments,
        \DateTimeInterface $rangeStart,
        \DateTimeInterface $rangeEnd,
        int $slotDurationMinutes = 30
    ): array {
        $availableSlots = [];
        $currentStart = $rangeStart;

        // Iterar por el rango en incrementos de slotDurationMinutes
        while ($currentStart < $rangeEnd) {
            $currentEnd = $currentStart->modify("+{$slotDurationMinutes} minutes");

            // Si el slot termina después del rango, parar
            if ($currentEnd > $rangeEnd) {
                break;
            }

            // Verificar si este slot está disponible
            if ($this->isSlotAvailable($appointments, $currentStart, $currentEnd)) {
                $availableSlots[] = [
                    'start' => $currentStart->format('Y-m-d H:i:s'),
                    'end' => $currentEnd->format('Y-m-d H:i:s'),
                ];
            }

            // Mover al siguiente slot
            $currentStart = $currentEnd;
        }

        return $availableSlots;
    }
}
