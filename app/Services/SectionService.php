<?php

namespace App\Services;

use App\Models\Section;
use App\Models\StampActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SectionService
{
    public function __construct(private StampService $stampService) {}

    /**
     * Elimina una sección de forma segura junto con todo su contenido:
     * 1. Captura los stamps afectados y el section_type_id antes de borrar.
     * 2. Elimina en lote los stamp_activities de todas las actividades de la sección.
     * 3. Soft-delete de las actividades y de la sección.
     * 4. Borra los archivos físicos del storage.
     * 5. Recalcula una sola vez el porcentaje de avance del section_type afectado.
     * 6. Reevalúa la completitud para transicionar a revisión las certificaciones que
     *    quedaron completos al excluir la sección eliminada.
     *
     * El orden es crítico: stamp_activities y actividades deben quedar
     * eliminados antes del recálculo para que numerador (stamp_activities
     * completadas) y denominador (Activity::count del tipo) reflejen el
     * estado final. Las mutaciones de BD se ejecutan en una transacción;
     * los archivos se borran solo tras confirmar el commit.
     */
    public function destroy(Section $section, string $reason, ?int $deletedBy = null): bool
    {
        $deletedBy = $deletedBy ? (int) $deletedBy : (int) auth()->id();
        $sectionTypeId = $section->section_type_id;

        $activities = $section->activities()->get();
        $activityIds = $activities->pluck('id')->all();

        if (empty($activityIds)) {
            // Sección sin actividades: el denominador no cambia, nada que recalcular.
            $section->deleted_reason = $reason;
            $section->deleted_by = $deletedBy;
            $section->save();

            return (bool) $section->delete();
        }

        $deleted = DB::transaction(function () use ($section, $activityIds, $reason, $deletedBy) {
            // 1. Eliminar en lote los stamp_activities de todas las actividades.
            StampActivity::whereIn('activity_id', $activityIds)->delete();

            // 2. Soft-delete de las actividades con metadata de eliminación.
            $section->activities()->update([
                'deleted_reason' => $reason,
                'deleted_by' => $deletedBy,
            ]);
            $section->activities()->delete();

            // 3. Soft-delete de la sección.
            $section->deleted_reason = $reason;
            $section->deleted_by = $deletedBy;
            $section->save();

            return (bool) $section->delete();
        });

        if (! $deleted) {
            return false;
        }

        // 4. Borrar archivos físicos solo tras el commit exitoso.
        foreach ($activities as $activity) {
            if ($activity->file_url) {
                Storage::disk('public')->delete($activity->file_url);
            }
            if ($activity->file_url_content) {
                Storage::disk('public')->delete($activity->file_url_content);
            }
        }

        // 5-6. Recalcular avance para TODOS los stamps (incluye usuarioes que
        //      nunca entregaron nada para estas actividades: su denominador también
        //      se redujo) y transicionar a revisión los que alcancen el 100%.
        if ($sectionTypeId) {
            $readyForReview = $this->stampService->generateAdvanceBulkForAll($sectionTypeId);
            $this->stampService->reevaluateCompletionForStamps($readyForReview);
        }

        return true;
    }
}
