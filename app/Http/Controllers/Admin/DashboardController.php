<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderStampResource;
use App\Services\Admin\DashboardService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Group('Admin/Dashboard')]
class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
        $this->middleware('ability:2.validador.dashboard.listar')->only('index');
        $this->middleware('ability:2.validador.dashboard.estatus.listar')->only('stampStatusesWithTotals');
    }

    public function resume(): JsonResponse
    {
        $resume = $this->dashboardService->getResume();

        return response()->json($resume);
    }

    public function stampStatusesWithTotals(Request $request): JsonResponse
    {
        $statuses = $this->dashboardService->getStampStatusesWithCountProviders($request);

        return response()->json($statuses);
    }

    public function index(Request $request): JsonResource
    {
        $stamps = $this->dashboardService->getPaginatedStamps($request);

        return ProviderStampResource::collection($stamps);
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->dashboardService->export($request);
    }

    public function staleAlert(): JsonResponse
    {
        return response()->json([
            'has_stale' => $this->dashboardService->hasStaleAssignedStamps(),
        ]);
    }
}
