<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * TreatmentController - API REST para catálogo de tratamientos
 */
class TreatmentController extends Controller
{
    /**
     * GET /api/treatments
     */
    public function index(): JsonResponse
    {
        $treatments = Treatment::all();
        return response()->json($treatments);
    }

    /**
     * POST /api/treatments
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'duration_minutes' => 'required|integer|min:15|max:480',
            ]);

            $treatment = Treatment::create($validated);

            return response()->json($treatment, 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * GET /api/treatments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $treatment = Treatment::find($id);

        if (!$treatment) {
            return response()->json(['message' => 'Tratamiento no encontrado'], 404);
        }

        return response()->json($treatment);
    }

    /**
     * PUT /api/treatments/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $treatment = Treatment::find($id);

        if (!$treatment) {
            return response()->json(['message' => 'Tratamiento no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'duration_minutes' => 'sometimes|required|integer|min:15|max:480',
            ]);

            $treatment->update($validated);

            return response()->json($treatment);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * DELETE /api/treatments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $treatment = Treatment::find($id);

        if (!$treatment) {
            return response()->json(['message' => 'Tratamiento no encontrado'], 404);
        }

        $treatment->delete();

        return response()->json(null, 204);
    }
}
