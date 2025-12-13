<?php

namespace App\Domain\Schedule;

// This is an optional placeholder to show where a framework-agnostic
// ClinicSchedule component could live. You are free to delete or modify this.
class ClinicSchedule
{
    /**
     * Check if a new time slot is available given an array of existing appointments.
     *
     * @param array $appointments
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return bool
     */
    public function isSlotAvailable(array $appointments, \DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        // Implement your scheduling logic here.
        return true;
    }
}
