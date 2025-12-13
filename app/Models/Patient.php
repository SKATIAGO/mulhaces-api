<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * fillable = campos que se pueden asignar masivamente con Patient::create()
     * Protege contra mass-assignment vulnerabilities
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
    ];

    /**
     * The attributes that should be cast.
     * 
     * Cast = convierte automáticamente tipos de datos
     * 'date_of_birth' se convertirá a Carbon\Carbon (manejo de fechas)
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Relación: Un paciente tiene muchas citas
     * 
     * Permite hacer: $patient->appointments
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
