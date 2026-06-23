<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    public function resume(): JsonResponse
    {
        $resume = $this->dashboardService->getResume();

        return response()->json($resume);
    }

    public function getStatusWithTotalsActivities(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'section_type_id']);
        $statuses = $this->dashboardService->getStatusWithTotalsActivities($filters);

        return response()->json($statuses);
    }

    public function getStampActivities(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'section_type_id', 'status_id']);
        $stampActivities = $this->dashboardService->getStampActivities($filters);

        return response()->json($stampActivities);
    }
}
