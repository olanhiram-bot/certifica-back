<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Stamp;
use App\Models\StampActivity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StampActivityService
{
    protected StampService $stampService;

    public function __construct(StampService $stampService)
    {
        $this->stampService = $stampService;

    }

    public function getStampActivityReviewer(StampActivity $stampActivity): void
    {
        $stamp = $stampActivity->stamp;

        if (! $stamp instanceof Stamp) {
            return;
        }

        // Obtenemos el total de actividades
        $totalRequiredActivities = Activity::whereHas('section', function ($query) {
            $query->whereIn('section_type_id', [1, 2]);
        })->count();

        // Obtenemos los conteos de estados específicos del Stamp
        $statusCounts = $stamp->stampsActivity()
            ->selectRaw('stamp_activity_status_id, count(*) as total')
            ->whereIn('stamp_activity_status_id', [1, 5, 6]) // 1: sin iniciar, 5: Rechazado, 6: Completado
            ->groupBy('stamp_activity_status_id')
            ->pluck('total', 'stamp_activity_status_id');

        $completedCount = $statusCounts->get(6, 0);
        $rejectedCount = $statusCounts->get(5, 0);
        $notStartedCount = $statusCounts->get(1, 0);
        $totalProcessed = $completedCount + $rejectedCount + $notStartedCount;

        if ($rejectedCount > 0 && $totalProcessed === $totalRequiredActivities) {
            $this->stampService->rejectStamp($stamp);
        }
    }

    public function uploadActivityFile(StampActivity $stampActivity, UploadedFile $file): StampActivity
    {
        // Borrar archivo anterior si existe
        if ($stampActivity->file_path) {
            Storage::disk('local')->delete($stampActivity->file_path);
        }

        $userPath = auth()->user()?->username ?? 'unnamed';
        $sizeInfo = formatFileSize($file->getSize(), 'array');

        $path = $file->store(
            "provider/{$userPath}/stamp/{$stampActivity->stamp_id}/activity/{$stampActivity->activity_id}",
            'local',
        );

        $stampActivity->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'file_size' => (float) ($sizeInfo['size'] ?? 0),
            'unit_size' => $sizeInfo['unit'] ?? 'KB',
            'upload_date' => now(),
            'stamp_activity_status_id' => 3, // Asumiendo 'Subido/Pendiente'
        ]);

        return $stampActivity;
    }
}
