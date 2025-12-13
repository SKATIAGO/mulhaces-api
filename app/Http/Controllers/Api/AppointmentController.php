<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * AppointmentController - API REST para gestión de citas
 * 
 * Usa AppointmentService para lógica de negocio (scheduling, validaciones)
 */
class AppointmentController extends Controller
{
    private AppointmentService $appointmentService;

    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * GET /api/appointments
     * 
     * Lista citas con filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with(['patient', 'treatment']);

        // Filtro por fecha: ?date=2025-12-13
        if ($request->has('date')) {
            $query->whereDate('start_time', $request->query('date'));
        }

        // Filtro por paciente: ?patient_id=1
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        // Filtro por estado: ?status=scheduled
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $appointments = $query->orderBy('start_time')->paginate(15);

        return response()->json($appointments);
    }

    /**
     * POST /api/appointments
     * 
     * Crea una nueva cita con validación de disponibilidad
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'treatment_id' => 'required|exists:treatments,id',
                'start_time' => 'required|date|after:now',
                'end_time' => 'nullable|date|after:start_time',
                'status' => 'nullable|in:scheduled,confirmed,completed,cancelled',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Usar el Service para crear (incluye validación de disponibilidad)
            $appointment = $this->appointmentService->createAppointment($validated);

            return response()->json($appointment->load(['patient', 'treatment']), 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409); // HTTP 409 Conflict
        }
    }

    /**
     * GET /api/appointments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['patient', 'treatment'])->find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        return response()->json($appointment);
    }

    /**
     * PUT /api/appointments/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'patient_id' => 'sometimes|required|exists:patients,id',
                'treatment_id' => 'sometimes|required|exists:treatments,id',
                'start_time' => 'sometimes|required|date',
                'end_time' => 'nullable|date|after:start_time',
                'status' => 'nullable|in:scheduled,confirmed,completed,cancelled',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Usar Service para actualizar (valida disponibilidad si cambia horario)
            $appointment = $this->appointmentService->updateAppointment($appointment, $validated);

            return response()->json($appointment->load(['patient', 'treatment']));

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * DELETE /api/appointments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $appointment->delete();

        return response()->json(null, 204);
    }

    /**
     * GET /api/appointments/available-slots
     * 
     * Endpoint especial para obtener horarios disponibles
     * 
     * Ejemplo: GET /api/appointments/available-slots?date=2025-12-14&duration=30
     */
    public function availableSlots(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'duration' => 'nullable|integer|min:15|max:240',
            ]);

            $date = Carbon::parse($validated['date']);
            $duration = $validated['duration'] ?? 30;

            $slots = $this->appointmentService->getAvailableSlots($date, $duration);

            return response()->json([
                'date' => $date->format('Y-m-d'),
                'duration_minutes' => $duration,
                'available_slots' => $slots,
                'total_available' => count($slots),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
