<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CurrentCourse;
use App\Http\Requests\MotiveDeleteRequest;
use App\Http\Requests\StoreStampRequest;
use App\Http\Resources\StampResource;
use App\Models\CourseCall;
use App\Models\Stamp;
use App\Models\StampActivityStatus;
use App\Services\NotificationService;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    public function __construct(
        protected StampService $stampService,
        protected NotificationService $notificationService,
    ) {
        $this->middleware(CurrentCourse::class)->only(['store']);
        $this->middleware('ability:2.validador.capacitacion.aceptar')->only('takeReviewerStamp');
        $this->middleware('ability:2.validador.capacitacion.concluir_expediente')->only('releaseStamp');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResource
    {
        $query = Stamp::orderBy('id', 'desc');

        if (request()->has('page')) {
            $stamp = $query->paginate(10)->onEachSide(1);
        } else {
            $stamp = $query->get();
        }

        return StampResource::collection($stamp);
    }

    public function lastByProvider(): JsonResource|JsonResponse
    {
        $providerId = auth()->id();
        $stamp = Stamp::where('provider_id', $providerId)->latest()->first();

        if (! $stamp) {
            return response()->json(['error' => 'Stamp not found'], 404);
        }

        return new StampResource($stamp->load(['provider', 'stampStatus']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStampRequest $request): JsonResponse|JsonResource
    {
        $data = $request->validated();
        $course = CourseCall::currentToday()->first();
        if (! $course) {
            return response()->json(['message' => 'No éxiste una convocatoria vigente'], 400);
        }
        $data['course_call_id'] = $course->id;

        $data['provider_id'] = auth()->id();
        $stamp = Stamp::create($data);

        return (new StampResource($stamp))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Stamp $stamp): JsonResource|JsonResponse
    {
        $user = Auth::user();
        $userId = (int) $user?->id;

        if ($user && $user->auth_method_id == 1) {
            // Usuario: solo puede consultar su propio expediente.
            if ($stamp->provider_id != $userId) {
                return response()->json(['message' => 'No se puede acceder a este expediente'], 422);
            }
        } elseif ($user && $stamp->reviewer_id !== null && $stamp->reviewer_id !== $userId) {
            // Revisor/admin: una vez asignado a otro revisor, queda bloqueado para el resto.
            return response()->json(['message' => 'El expediente ya fue tomado por otro revisor'], 403);
        }

        $stamp->load(['provider', 'stampStatus']);

        return new StampResource($stamp);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MotiveDeleteRequest $request, Stamp $stamp): JsonResponse
    {
        $stamp->stampsActivity->each(function ($activity) {
            $activity->stampActivityComments()->delete();
            $activity->delete();
        });
        $stamp->deleted_reason = $request->input('deleted_reason');
        $stamp->deleted_by = (int) Auth::id();
        $stamp->save();
        $stamp->stampsActivity()->delete();
        $deleted = $stamp->delete();

        return response()->json(null, $deleted ? 204 : 400);
    }

    public function getStamActivitiesByStampStatus(Stamp $stamp, StampActivityStatus $stampStatus): JsonResponse
    {
        $response = $stamp->stampsActivity()->where('stamp_activity_status_id', $stampStatus->id)->get();

        return response()->json($response);
    }

    /**
     * Take reviewer stamp
     */
    public function takeReviewerStamp(Stamp $stamp): JsonResource|JsonResponse
    {
        if ($stamp->reviewer_id) {
            return response()->json(['message' => 'El expediente ya fue asignado'], 400);
        }

        $stamp->reviewer_id = (int) auth()->id();
        $stamp->take_review_date = now();
        $stamp->stamp_status_id = 3; // En Revision
        $stamp->save();

        // se envía notificación al usuario que su expediente fue tomado por un revisor
        $this->notificationService->sendStampNotification($stamp, 2);

        return new StampResource($stamp);
    }

    public function releaseStamp(Stamp $stamp): JsonResource|JsonResponse
    {
        if ($stamp->reviewer_id !== auth()->id()) {
            return response()->json(['message' => 'No tienes permiso para liberar este expediente'], 403);
        }
        if ($stamp->percent < 100) {
            return response()->json(['message' => 'Faltan actividades por completar'], 422);
        }

        $this->stampService->approveStamp($stamp);

        return new StampResource($stamp);
    }

    public function approve(Stamp $stamp): JsonResource|JsonResponse
    {
        return $this->releaseStamp($stamp);
    }

    public function reject(Stamp $stamp): JsonResource|JsonResponse
    {
        if ($stamp->reviewer_id !== auth()->id()) {
            return response()->json(['message' => 'No tienes permiso para rechazar este expediente'], 403);
        }

        $this->stampService->rejectStamp($stamp);

        return new StampResource($stamp);
    }
}
