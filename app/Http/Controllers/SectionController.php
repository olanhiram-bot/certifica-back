<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotiveDeleteRequest;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Models\SectionType;
use App\Models\Stamp;
use App\Services\SectionService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class SectionController extends Controller
{
    private SectionService $sectionService;

    public function __construct(SectionService $sectionService)
    {
        $this->middleware('ability:2.admin.secciones.guardar')->only('store');
        $this->middleware('ability:2.admin.secciones.actualizar')->only('update');
        $this->middleware('ability:2.admin.secciones.eliminar')->only('destroy');

        $this->sectionService = $sectionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(SectionType $sectionsType): JsonResource
    {
        $lastStampId = Stamp::where('provider_id', auth()->id())->latest()->value('id');

        // Si se pasa stamp_id (admin/validador revisando un expediente específico)
        // y el usuario autenticado NO es usuario, usar ese stamp_id.
        // Esto evita que un usuario consulte stamps ajenos.
        $requestedStampId = request()->integer('stamp_id') ?: null;
        $stampId = (! $lastStampId && $requestedStampId) ? $requestedStampId : $lastStampId;

        $with = [
            'activities' => function ($query) {
                $query->orderBy('id', 'asc');
            },
        ];

        // cargar solo las stampActivity del stamp resuelto;
        // si $stampId === null no se carga ninguna stampActivity.
        if ($stampId) {
            $with['activities.stampsActivity'] = function ($query) use ($stampId) {
                $query->where('stamp_id', $stampId)->with('status');
            };
        }

        $query = Section::with($with)->where('section_type_id', $sectionsType->id)->orderBy('id', 'asc');

        if (request()->has('page')) {
            $sections = $query->paginate(10)->onEachSide(1);
        } else {
            $sections = $query->get();
        }

        return SectionResource::collection($sections);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request, SectionType $sectionsType): JsonResource
    {
        $section = $sectionsType->sections()->create($request->validated());

        return new SectionResource($section);
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section): JsonResource
    {
        return new SectionResource($section);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section): JsonResource
    {
        $data = $request->validated();

        $section->fill($data)->save();

        return new SectionResource($section);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MotiveDeleteRequest $request, Section $section): JsonResponse
    {
        $userId = (int) Auth::id();
        $reason = $request->input('deleted_reason');
        $deleted = $this->sectionService->destroy($section, $reason, $userId);

        return response()->json(null, $deleted ? 204 : 400);
    }
}
