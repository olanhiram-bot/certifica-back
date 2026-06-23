<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Models\StampActivityStatus;

class DashboardService
{
    private int $diasProvSubirTodo;
    private int $diasProvCorregir;

    public function __construct()
    {
        $this->diasProvSubirTodo = config('app.dias_prov_subir_todo');
        $this->diasProvCorregir = config('app.dias_prov_corregir');
    }

    /**
     * Días restantes: 10 - transcurso de días de creado el stamp
     * Comentarios: Total de comentarios creados en el stamp
     * Evidencias por subir: Total de evidencias que faltan por subir para completar el stamp en section_type_id 1
     * Protocolos por subir: Total de evidencias que faltan por subir para completar el stamp en section_type_id 2
     *
     * @return array<string, int>
     */
    public function getResume(): array
    {
        $stamp = Stamp::query()->select('id', 'stamp_status_id', 'reject_date', 'created_at')->where('provider_id', auth()->id())->latest()->first();

        $totalActivitiesType1 = Activity::query()
            ->whereHas('section', fn ($q) => $q->where('section_type_id', 1))
            ->count();
        $totalActivitiesType2 = Activity::query()
            ->whereHas('section', fn ($q) => $q->where('section_type_id', 2))
            ->count();

        if (! $stamp) {
            return [
                'days_remaining' => $this->diasProvSubirTodo,
                'comments' => 0,
                'evidences_to_upload' => $totalActivitiesType1,
                'protocols_to_upload' => $totalActivitiesType2,
            ];
        }
        $stampId = $stamp->id;
        // Calcular días restantes como: dias_prov - días transcurridos desde created_at/reject_date
        $daysRemaining = 0;
        if ($stamp->stamp_status_id == 1 && $stamp->created_at) {
            $daysPassed = (int) $stamp->created_at->diffInDays(now());
            $remaining = $this->diasProvSubirTodo - $daysPassed;
            $daysRemaining = max(0, workingDay($remaining));
        } elseif ($stamp->stamp_status_id == 6 && $stamp->reject_date) {
            $daysPassed = (int) $stamp->reject_date->diffInDays(now());
            $remaining = $this->diasProvCorregir - $daysPassed;
            $daysRemaining = max(0, workingDay($remaining));
        }

        // Total de comentarios
        $comments = StampActivityComment::whereHas('stampActivity', function ($q) use ($stampId) {
            $q->where('stamp_id', $stampId);
        })->count();

        $stampActivitybuilder = StampActivity::query()->where('stamp_id', $stampId);

        // Evidencias de módulos por subir
        $uploadedType1 = (clone $stampActivitybuilder)
            ->whereNotNull('file_path')
            ->whereHas('activity.section', fn ($q) => $q->where('section_type_id', 1))
            ->count();
        $evidencesToUpload = max(0, $totalActivitiesType1 - $uploadedType1);

        // Evidencias de protocolos por subir
        $uploadedType2 = (clone $stampActivitybuilder)
            ->whereNotNull('file_path')
            ->whereHas('activity.section', fn ($q) => $q->where('section_type_id', 2))
            ->count();
        $protocolsToUpload = max(0, $totalActivitiesType2 - $uploadedType2);

        return [
            'days_remaining' => $daysRemaining,
            'comments' => $comments,
            'evidences_to_upload' => $evidencesToUpload,
            'protocols_to_upload' => $protocolsToUpload,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters  Accepted keys: search, section_type_id
     * @return array<int, array<string, mixed>>
     */
    public function getStatusWithTotalsActivities(array $filters = []): array
    {
        $search = $filters['search'] ?? null;
        $sectionTypeId = $filters['section_type_id'] ?? null;

        $stamps = StampActivityStatus::query()
            ->select('id', 'name')
            ->withCount(['stampActivities' => function ($q) use ($search, $sectionTypeId) {
                $q->whereHas('stamp', fn ($q) => $q->where('provider_id', auth()->id()))
                    ->search($search);

                if ($sectionTypeId) {
                    $q->whereHas('activity.section', fn ($q) => $q->where('section_type_id', $sectionTypeId));
                }
            }])->get();

        return $stamps->map(function (StampActivityStatus $stamp) {
            return [
                'stamp_id' => $stamp->id,
                'status' => $stamp->name,
                'total_activities' => $stamp->stamp_activities_count,
            ];
        })->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters  Accepted keys: search, section_type_id, status_id
     * @return array<never>|\Illuminate\Pagination\LengthAwarePaginator<\App\Models\StampActivity>
     */
    public function getStampActivities(array $filters = []): array|\Illuminate\Pagination\LengthAwarePaginator
    {
        $stamp = Stamp::query()->select('id')->where('provider_id', auth()->id())->latest()->first();

        if (! $stamp) {
            return [];
        }

        $search = $filters['search'] ?? null;
        $sectionTypeId = $filters['section_type_id'] ?? null;
        $statusId = $filters['status_id'] ?? null;

        $query = StampActivity::query()
            ->select('id', 'activity_id', 'stamp_activity_status_id', 'created_at')
            ->with([
                'activity' => fn ($q) => $q->select('id', 'name', 'section_id'),
                'activity.section' => fn ($q) => $q->select('id', 'name'),
                'status' => fn ($q) => $q->select('id', 'name', 'hexadecimal'),
            ])
            ->where('stamp_id', $stamp->id)
            ->search($search);

        if ($sectionTypeId) {
            $query->whereHas('activity.section', fn ($q) => $q->where('section_type_id', $sectionTypeId));
        }

        if ($statusId) {
            $query->where('stamp_activity_status_id', $statusId);
        }

        return $query->paginate(10);
    }
}
