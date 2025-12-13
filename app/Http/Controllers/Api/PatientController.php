<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * PatientController - API REST para gestión de pacientes
 * 
 * Endpoints:
 * - GET    /api/patients       → Listar todos los pacientes
 * - POST   /api/patients       → Crear nuevo paciente
 * - GET    /api/patients/{id}  → Ver detalles de un paciente
 * - PUT    /api/patients/{id}  → Actualizar paciente
 * - DELETE /api/patients/{id}  → Eliminar paciente
 */
class PatientController extends Controller
{
    /**
     * GET /api/patients
     * 
     * Lista todos los pacientes, con opción de incluir sus citas
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Query base
        $query = Patient::query();

        // Si se solicita incluir appointments: ?include=appointments
        if ($request->query('include') === 'appointments') {
            $query->with('appointments');
        }

        // Paginación (por defecto 15 items)
        $perPage = min($request->query('per_page', 15), 100); // Máximo 100
        $patients = $query->paginate($perPage);

        return response()->json($patients);
    }

    /**
     * POST /api/patients
     * 
     * Crea un nuevo paciente
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:patients,email',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
            ]);

            // Crear paciente
            $patient = Patient::create($validated);

            // Respuesta HTTP 201 Created
            return response()->json($patient, 201);

        } catch (ValidationException $e) {
            // Respuesta HTTP 422 Unprocessable Entity
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * GET /api/patients/{id}
     * 
     * Muestra los detalles de un paciente específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $patient = Patient::with('appointments')->find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        return response()->json($patient);
    }

    /**
     * PUT /api/patients/{id}
     * 
     * Actualiza los datos de un paciente
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        try {
            // Validar (email debe ser único excepto para este paciente)
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:patients,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
            ]);

            $patient->update($validated);

            return response()->json($patient);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * DELETE /api/patients/{id}
     * 
     * Elimina un paciente (y sus citas por CASCADE)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        $patient->delete();

        // HTTP 204 No Content (sin cuerpo de respuesta)
        return response()->json(null, 204);
    }
}

