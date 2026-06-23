<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddStampActivityFileRequest;
use App\Http\Requests\StoreStampActivityRequest;
use App\Http\Resources\StampActivityResource;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Services\StampActivityService;
use App\Services\StampService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StampActivityController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StampService $stampService,
        protected StampActivityService $stampActivityService,
    ) {}

    public function index(Stamp $stamp): JsonResource
    {
        $query = $stamp->stampsActivity()
            ->with('status')
            ->orderBy('id', 'desc');
        if (request()->has('page')) {
            $resp = $query->paginate(10)->onEachSide(1);
        } else {
            $resp = $query->get();
        }

        return StampActivityResource::collection($resp);
    }

    public function store(StoreStampActivityRequest $request, Stamp $stamp): StampActivityResource
    {
        $this->stampService->validateStampCurrent($stamp);
        $stampActivity = StampActivity::firstOrCreate(
            ['stamp_id' => $stamp->id, 'activity_id' => $request->activity_id],
            ['stamp_activity_status_id' => 1],
        );

        return new StampActivityResource($stampActivity);
    }

    /**
     * Display the specified resource.
     */
    public function show(StampActivity $stampActivity): JsonResource
    {
        return new StampActivityResource($stampActivity->load(['activity', 'status']));
    }

    /**
     * Add file in StampActivity
     */
    public function addFile(AddStampActivityFileRequest $request, StampActivity $stampActivity): JsonResource|JsonResponse
    {
        // 1. Validaciones de estado (Podrían ir en un Policy)
        if (in_array($stampActivity->stamp_activity_status_id, [4, 6])) {
            return $this->errorResponse('No se puede subir evidencia en el estatus actual', 403);
        }

        try {
            $this->stampService->validateDateUploadEvidence($stampActivity);

            // 2. Delegar proceso de archivo al servicio
            $this->stampActivityService->uploadActivityFile($stampActivity, $request->file('file'));

            // 3. Procesos posteriores
            $evidenceComplete = $this->stampService->isSectionComplete($stampActivity);
            $this->stampService->generateAdvance($stampActivity);

            $response = new StampActivityResource($stampActivity->fresh(['status']));

            $response->additional(['meta' => [
                'evidence_complete' => $evidenceComplete,
                'message' => $evidenceComplete ? '¡Actividad completada! Todas las evidencias han sido cargadas.' : null,
            ],
            ]);

            return $response;
        } catch (\Exception $e) {

            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function viewMaterial(StampActivity $stampActivity): StampActivityResource
    {
        $stampActivity->update([
            'stamp_activity_status_id' => 2,
            'material_was_view' => true,
        ]);

        return new StampActivityResource($stampActivity->load(['activity', 'status']));
    }

    public function approvedStampActivity(StampActivity $stampActivity): JsonResource|JsonResponse
    {
        $stamp = $stampActivity->stamp;

        // Validaciones de negocio
        if (! $stamp) {
            return $this->errorResponse('Certificacion no encontrada', 404);
        }

        if ($stamp->reviewer_id != auth()->id()) {
            return $this->errorResponse('No tienes permiso para aprobar esta actividad', 403);
        }

        if ($stampActivity->stamp_activity_status_id != 4) {
            return $this->errorResponse('Estatus inválido para aprobación', 422);
        }

        try {
            $this->stampService->validateDateEvaluate($stamp);

            $stampActivity->update(['stamp_activity_status_id' => 6]);

            $this->stampActivityService->getStampActivityReviewer($stampActivity);

            $reviewComplete = $this->stampService->isReviewComplete($stampActivity);

            $response = new StampActivityResource($stampActivity->load(['activity', 'status', 'stamp']));

            $response->additional(['meta' => [
                'evidence_complete' => $reviewComplete,
                'message' => $reviewComplete ? '¡Revisión completada! Todas las evidencias han sido evaluadas.' : null,
            ]]);

            return $response;
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function rejectedStampActivity(StampActivity $stampActivity): JsonResource|JsonResponse
    {
        $stamp = $stampActivity->stamp;

        if (! $stamp) {
            return $this->errorResponse('Certificacion no encontrada', 404);
        }

        if ($stamp->reviewer_id != auth()->id()) {
            return $this->errorResponse('No tienes permiso para rechazar esta actividad', 403);
        }

        if ($stampActivity->stamp_activity_status_id != 4) {
            return $this->errorResponse('Estatus inválido para rechazo', 422);
        }

        try {
            $this->stampService->validateDateEvaluate($stamp);

            $stampActivity->update(['stamp_activity_status_id' => 5]);

            $this->stampActivityService->getStampActivityReviewer($stampActivity);

            $reviewComplete = $this->stampService->isReviewComplete($stampActivity);

            $response = new StampActivityResource($stampActivity->load(['activity', 'status', 'stamp']));

            $response->additional(['meta' => [
                'evidence_complete' => $reviewComplete,
                'message' => $reviewComplete ? 'Revisión completada. Hay evidencias rechazadas por corregir.' : null,
            ]]);

            return $response;
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function destroyFile(StampActivity $stampActivity): JsonResource|JsonResponse
    {
        if ($stampActivity->stamp_activity_status_id != 3) {
            return $this->errorResponse('No se puede eliminar la evidencia en este estatus', 403);
        }

        if ($stampActivity->file_path) {
            Storage::disk('local')->delete($stampActivity->file_path);
        }

        $stampActivity->update([
            'file_path' => null,
            'file_name' => null,
            'extension' => null,
            'upload_date' => null,
            'stamp_activity_status_id' => 2,
        ]);

        return new StampActivityResource($stampActivity);
    }

    public function serveFile(StampActivity $stampActivity): StreamedResponse|JsonResponse
    {
        if (! $stampActivity->file_path || ! Storage::disk('local')->exists($stampActivity->file_path)) {
            return $this->errorResponse('Archivo no encontrado', 404);
        }

        return Storage::disk('local')->response($stampActivity->file_path, $stampActivity->file_name);
    }
}
