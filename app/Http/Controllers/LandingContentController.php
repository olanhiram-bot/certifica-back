<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotiveDeleteRequest;
use App\Http\Requests\StoreLandingContentRequest;
use App\Http\Requests\UpdateLandingContentRequest;
use App\Http\Resources\LandingContentResource;
use App\Models\LandingContent;
use App\Models\LandingContentType;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LandingContentController extends Controller
{
    public function __construct()
    {
        $this->middleware('ability:2.admin.landing.guardar')->only('store');
        $this->middleware('ability:2.admin.landing.actualizar')->only('update');
        $this->middleware('ability:2.admin.landing.eliminar')->only('destroy');
    }

    public function index(Request $request, LandingContentType $contentType): JsonResource
    {
        $query = $contentType->landingContents()->with('landingContentType')->orderBy('position', 'asc');
        $filter = trim($request->get('filter'));
        if ($filter) {
            $query = $query->search($filter);
        }

        if (request()->has('page')) {
            $resp = $query->paginate(10)->onEachSide(1);
        } else {
            $resp = $query->get();
        }

        return LandingContentResource::collection($resp);

    }

    public function store(StoreLandingContentRequest $request, LandingContentType $contentType): JsonResource
    {
        $data = $request->validated();

        if ($request->hasFile('file_url')) {
            $path = $request->file('file_url')->store('landing', 'public');
            $data['file_url'] = $path;
        }

        $resp = $contentType->landingContents()->create($data);

        return new LandingContentResource($resp);
    }

    public function show(LandingContent $landingContent): JsonResource
    {
        return new LandingContentResource($landingContent);
    }

    public function update(UpdateLandingContentRequest $request, LandingContent $landingContent): JsonResource
    {
        $data = $request->validated();

        // If a new file is uploaded, delete the existing file from storage
        if ($request->hasFile('file_url')) {
            if ($landingContent->file_url) {
                Storage::disk('public')->delete($landingContent->file_url);
            }
            $path = $request->file('file_url')->store('landing', 'public');
            $data['file_url'] = $path;
        }
        $landingContent->fill($data)->save();

        return new LandingContentResource($landingContent);

    }

    public function destroy(MotiveDeleteRequest $request, LandingContent $landingContent): JsonResponse
    {
        if ($landingContent->file_url) {
            Storage::disk('public')->delete($landingContent->file_url);
        }

        $landingContent->deleted_reason = $request->input('deleted_reason');
        $landingContent->deleted_by = (int) Auth::id();
        $landingContent->save();

        $resp = $landingContent->delete();
        $error_code = $resp ? 200 : 400;

        return response()->json($resp, $error_code);
    }
}
