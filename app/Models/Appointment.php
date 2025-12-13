<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'patient_id',
        'treatment_id',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    /**
     * Cast de tipos
     * 
     * start_time y end_time se convertirán a Carbon (objetos de fecha/hora)
     * Permite hacer: $appointment->start_time->format('H:i')
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Relación: Una cita pertenece a un paciente
     * 
     * Permite hacer: $appointment->patient
     * Laravel busca automáticamente por 'patient_id'
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación: Una cita pertenece a un tratamiento
     * 
     * Permite hacer: $appointment->treatment
     */
    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }
}
