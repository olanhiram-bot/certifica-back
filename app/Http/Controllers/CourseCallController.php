<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseCallRequest;
use App\Http\Requests\UpdateCourseCallRequest;
use App\Http\Resources\CourseCallResource;
use App\Models\CourseCall;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\JsonResponse;

class CourseCallController extends Controller
{
    public function __construct()
    {
        $this->middleware('ability:2.admin.convocatoria.listar')->only('index');
        $this->middleware('ability:2.admin.convocatoria.crear')->only('store');
        $this->middleware('ability:2.admin.convocatoria.ver')->only('show');
        $this->middleware('ability:2.admin.convocatoria.actualizar')->only('update');
        $this->middleware('ability:2.admin.convocatoria.eliminar')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResource
    {
        $query = CourseCall::orderBy('id', 'desc');

        if (request()->has('page')) {
            $resp = $query->paginate(10)->onEachSide(1);
        } else {
            $resp = $query->get();
        }

        return CourseCallResource::collection($resp);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseCallRequest $request): JsonResource|JsonResponse
    {
        $data = $request->validated();
        $course = CourseCall::currentToday()->first();
        if ($course) {
            return response()->json(['error' => 'Ya existe una convocatoria vigente'], 400);
        }
        $courseCall = CourseCall::create($data);

        return new CourseCallResource($courseCall);
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseCall $courseCall): JsonResource
    {
        return new CourseCallResource($courseCall);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseCallRequest $request, CourseCall $courseCall): JsonResource
    {
        $data = $request->validated();
        $courseCall->fill($data)->save();

        return new CourseCallResource($courseCall);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseCall $courseCall): JsonResponse
    {
        $deleted = $courseCall->delete();

        return response()->json(null, $deleted ? 204 : 400);
    }
}
