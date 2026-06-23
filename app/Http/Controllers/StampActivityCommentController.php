<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotiveDeleteRequest;
use App\Http\Requests\StoreStampActivityCommentRequest;
use App\Http\Requests\UpdateStampActivityCommentRequest;
use App\Http\Resources\StampActivityCommentResource;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Services\StampActivityService;
use App\Services\StampService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class StampActivityCommentController extends Controller
{
    public function __construct(
        protected StampService $stampService,
        protected StampActivityService $stampActivityService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(StampActivity $stampActivity): JsonResource
    {
        $query = $stampActivity->stampActivityComments()->orderBy('created_at', 'desc');

        if (request()->has('page')) {
            $resp = $query->paginate(10)->onEachSide(1);
        } else {
            $resp = $query->get();
        }

        return StampActivityCommentResource::collection($resp);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStampActivityCommentRequest $request, StampActivity $stampActivity): JsonResource|JsonResponse
    {
        $data = $request->validated();
        $stamp = $stampActivity->stamp;
        if ($stamp) {
            if ($stamp->reviewer_id != Auth::id()) {
                return response()->json(['message' => 'Solo el revisor asignado puede agregar comentarios'], 422);
            }
        }
        $stampActivityComment = $stampActivity->stampActivityComments()->create($data);
        $stampActivity->stamp_activity_status_id = 5;
        $stampActivity->save();
        $this->stampActivityService->getStampActivityReviewer($stampActivity);

        $this->stampService->generateAdvance($stampActivity);

        $reviewComplete = $this->stampService->isReviewComplete($stampActivity);

        $response = new StampActivityCommentResource($stampActivityComment);

        $response->additional(['meta' => [
            'evidence_complete' => $reviewComplete,
            'message' => $reviewComplete ? '¡Revisión completada! Todas las evidencias han sido evaluadas.' : null,
        ]]);

        return $response;
    }

    /**
     * Display the specified resource.
     */
    public function show(StampActivityComment $comment): JsonResource
    {
        return new StampActivityCommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStampActivityCommentRequest $request, StampActivityComment $comment): JsonResource
    {
        $data = $request->validated();
        $comment->fill($data)->save();

        return new StampActivityCommentResource($comment);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MotiveDeleteRequest $request, StampActivityComment $comment): JsonResponse
    {
        $comment->deleted_reason = $request->input('deleted_reason');
        $comment->deleted_by = (int) Auth::id();
        $comment->save();

        $deleted = $comment->delete();

        return response()->json(null, $deleted ? 204 : 400);
    }
}
