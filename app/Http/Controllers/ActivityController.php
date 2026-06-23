<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotiveDeleteRequest;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Section;
use App\Services\ActivityService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function __construct(protected ActivityService $activityService)
    {
        $this->middleware('ability:2.admin.actividad.guardar')->only('store');
        $this->middleware('ability:2.admin.actividad.actualizar')->only('update');
        $this->middleware('ability:2.admin.actividad.eliminar')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Section $section): JsonResource
    {
        $query = $section->activities()->orderBy('id', 'desc');

        if (request()->has('page')) {
            $resp = $query->paginate(10)->onEachSide(1);
        } else {
            $resp = $query->get();
        }

        return ActivityResource::collection($resp);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request, Section $section): JsonResource
    {
        $data = $request->validated();

        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('activities', 'public');
            $data['file_url'] = $path;
        }

        if ($request->hasFile('content_file')) {
            $content_path = $request->file('content_file')->store('activities', 'public');
            $data['file_url_content'] = $content_path;
        }

        $activity = $section->activities()->create($data);
        if ($activity->id) {
            $this->activityService->updateAllStampsAndStampActivities($activity);
        }

        return new ActivityResource($activity);
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): JsonResource
    {
        return new ActivityResource($activity);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity): JsonResource
    {
        $data = $request->validated();
        if ($request->hasFile('logo_file')) {
            if ($activity->file_url) {
                Storage::disk('public')->delete($activity->file_url);
            }
            $path = $request->file('logo_file')->store('activities', 'public');
            $data['file_url'] = $path;
        }

        if ($request->hasFile('content_file')) {
            if ($activity->file_url_content) {
                Storage::disk('public')->delete($activity->file_url_content);
            }
            $content_path = $request->file('content_file')->store('activities', 'public');
            $data['file_url_content'] = $content_path;
        }

        if ($request->input('activity_logo_delete')) {
            if ($activity->file_url) {
                Storage::disk('public')->delete($activity->file_url);
            }
            $data['file_url'] = null;
        }

        if ($request->input('activity_content_delete')) {
            if ($activity->file_url_content) {
                Storage::disk('public')->delete($activity->file_url_content);
            }
            $data['file_url_content'] = null;
        }

        $activity->fill($data)->save();

        return new ActivityResource($activity);
    }

    /**
     * Devuelve el archivo de contenido (PDF) de la actividad como stream.
     * Al pasar por este controlador, el middleware CORS aplica correctamente.
     */
    public function content(Activity $activity): StreamedResponse|JsonResponse
    {
        if (! $activity->file_url_content || ! Storage::disk('public')->exists($activity->file_url_content)) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        return Storage::disk('public')->response($activity->file_url_content, null, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MotiveDeleteRequest $request, Activity $activity, ActivityService $activityService): JsonResponse
    {
        $deleted = $activityService->deleteActivity(
            $activity,
            $request->input('deleted_reason'),
            (int) Auth::id(),
        );

        return response()->json(null, $deleted ? 204 : 400);
    }
}
