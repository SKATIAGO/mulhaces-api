<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\TreatmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Todas las rutas aquí son prefijadas automáticamente con /api
| Ejemplo: Route::get('/patients') → http://localhost:8080/api/patients
|
*/

/**
 * Health Check - Verifica que la API está funcionando
 */
Route::get('/', function () {
    return response()->json([
        'message' => 'Dental Clinic API',
        'status' => 'running',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Ruta de autenticación (Sanctum) - descomentada para uso futuro
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

/**
 * Rutas de API REST - Recursos CRUD completos
 * 
 * Route::apiResource() automáticamente crea las 5 rutas REST:
 * - GET    /api/patients          → index()
 * - POST   /api/patients          → store()
 * - GET    /api/patients/{id}     → show()
 * - PUT    /api/patients/{id}     → update()
 * - DELETE /api/patients/{id}     → destroy()
 */

Route::apiResource('patients', PatientController::class);
Route::apiResource('treatments', TreatmentController::class);
Route::apiResource('appointments', AppointmentController::class);

/**
 * Ruta especial para obtener slots disponibles
 * 
 * GET /api/appointments/available-slots?date=2025-12-14&duration=30
 * 
 * Nota: Esta ruta debe ir ANTES de la ruta del resource
 * (sino /available-slots se interpreta como un ID)
 */
Route::get('appointments-available-slots', [AppointmentController::class, 'availableSlots'])
    ->name('appointments.available-slots');

