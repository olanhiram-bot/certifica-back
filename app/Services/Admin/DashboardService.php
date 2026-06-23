<?php

namespace App\Services\Admin;

use App\Exports\ValidatorStampsExport;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Models\StampStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardService
{
    /**
     * @return Builder<Stamp>
     */
    private function buildQuery(Request $request): Builder
    {
        // Subquery: latest stamp id per provider
        $latestPerProvider = Stamp::select(DB::raw('MAX(id) as id'))
            ->groupBy('provider_id');

        $authUserId = auth()->id();

        $query = Stamp::with(['provider', 'stampStatus', 'stampsActivity', 'modalityType'])
            ->whereIn('id', $latestPerProvider)
            ->where(function (Builder $q) use ($authUserId) {
                $q->whereNull('reviewer_id')
                    ->orWhere('reviewer_id', $authUserId);
            })
            ->orderBy('take_review_date', 'asc');

        if ($request->filled('stamp_status_id')) {
            $query->where('stamp_status_id', $request->integer('stamp_status_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->value();
            $query->whereHas('provider', function (Builder $q) use ($search) {
                /** @var Builder<User> $q */
                $q->search($search);
            });
        }

        if ($request->filled('modality_type_id')) {
            $query->where('modality_type_id', $request->integer('modality_type_id'));
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<Stamp>
     */
    public function getPaginatedStamps(Request $request): LengthAwarePaginator
    {
        return $this->buildQuery($request)->paginate(10);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filename = 'usuarioes_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ValidatorStampsExport($this->buildQuery($request)), $filename);
    }

    /**
     * Lista los StampStatus con un contador de usuarioes asociados a cada uno,
     * aplicando los mismos filtros que en el listado de usuarioes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStampStatusesWithCountProviders(Request $request): array
    {
        $filteredStamps = $this->buildQuery($request)->toBase();

        return StampStatus::query()
            ->leftJoinSub($filteredStamps, 'filtered_stamps', function ($join) {
                $join->on('stamp_statuses.id', '=', 'filtered_stamps.stamp_status_id');
            })
            ->select([
                'stamp_statuses.id',
                'stamp_statuses.name',
                'stamp_statuses.hexadecimal',
                DB::raw('COUNT(DISTINCT filtered_stamps.provider_id) as providers_count'),
            ])
            ->groupBy('stamp_statuses.id', 'stamp_statuses.name', 'stamp_statuses.hexadecimal')
            ->orderBy('stamp_statuses.id')
            ->get()
            ->toArray();
    }

    /**
     * Nuevos: Inscripciones sin asignación, reviewer_id en null.
     * Comentarios: Total de comentarios del usuario logueado.
     * Evidencias: Actividades revisadas por el usuario logueado y con modalidad 1.
     * Protocolos: Actividades revisadas por el usuario logueado y con modalidad 2.
     */
    /**
     * Retorna true si el usuario autenticado tiene expedientes asignados
     * (reviewer_id = auth()->id()) sin movimientos (updated_at) en los
     * últimos 2 días.
     */
    public function hasStaleAssignedStamps(): bool
    {
        $authUserId = auth()->id();
        $threshold = now()->subDays(2);

        return Stamp::query()
            ->where('reviewer_id', $authUserId)
            ->where('updated_at', '<', $threshold)
            ->exists();
    }

    /**
     * @return array<string, int>
     */
    public function getResume(): array
    {
        $authUserId = auth()->id();

        $nuevosCount = Stamp::query()
            ->whereNull('reviewer_id')
            ->count();

        // Suma de stamp_activity_comments
        $comentariosCount = StampActivityComment::query()
            ->whereHas('stampActivity', function (Builder $q) use ($authUserId) {
                $q->whereHas('stamp', function (Builder $q2) use ($authUserId) {
                    $q2->where('reviewer_id', $authUserId);
                });
            })
            ->count();

        $evidenciasCount = StampActivity::query()
            ->whereHas('stamp', function (Builder $q) use ($authUserId) {
                $q->where('reviewer_id', $authUserId)
                    ->where('modality_type_id', 1);
            })
            ->count();

        $protocolosCount = StampActivity::query()
            ->whereHas('stamp', function (Builder $q) use ($authUserId) {
                $q->where('reviewer_id', $authUserId)
                    ->where('modality_type_id', 2);
            })
            ->count();

        return [
            'news' => $nuevosCount,
            'comments' => $comentariosCount,
            'evidences' => $evidenciasCount,
            'protocols' => $protocolosCount,
        ];
    }
}
