<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_minutes',
    ];

    /**
     * Cast de tipos
     * 
     * price se casteará a float para operaciones matemáticas
     * duration_minutes se casteará a int
     */
    protected $casts = [
        'price' => 'decimal:2', // 2 decimales
        'duration_minutes' => 'integer',
    ];

    /**
     * Relación: Un tratamiento puede tener muchas citas
     * 
     * Permite hacer: $treatment->appointments
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
