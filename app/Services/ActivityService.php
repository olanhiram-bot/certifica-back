<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Stamp;
use App\Models\StampActivity;
use Illuminate\Support\Facades\Storage;

class ActivityService
{
    public function __construct(private StampService $stampService) {}

    public function updateAllStampsAndStampActivities(Activity $activity): void
    {

        // 1. Obtenemos un array asociativo donde la CLAVE es el 'id' y el VALOR es el 'stamp_status_id'
        $stamps = Stamp::whereIn('stamp_status_id', [2, 3, 6])->pluck('stamp_status_id', 'id');

        // 2. Mapeamos usando tanto la clave ($stampId) como el valor ($stampStatusId)
        $dataToInsert = $stamps->map(function ($stampStatusId, $stampId) use ($activity) {

            // Asignamos el ID del estado de la actividad según el estado dla certificacion
            $stampActivityStatusId = match ($stampStatusId) {
                2 => 4, // Por Revisar -> En Revisión
                3 => 4, // En Revisión -> En Revisión
                6 => 1, // Rechazado   -> Sin Iniciar
                default => 1,
            };

            return [
                'stamp_id' => $stampId,
                'activity_id' => $activity->id,
                'stamp_activity_status_id' => $stampActivityStatusId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values()->toArray(); // Usamos values() para resetear las claves y dejar un array limpio para el insert

        // 3. Insertamos en bloque
        StampActivity::insert($dataToInsert);
    }

    /**
     * Elimina una actividad de forma segura:
     * 1. Obtiene los stamps afectados y el section_type_id antes de borrar.
     * 2. Elimina en lote los stamp_activities asociados.
     * 3. Borra los archivos físicos del storage.
     * 4. Soft-delete de la actividad.
     * 5. Recalcula el porcentaje de avance de los stamps afectados.
     * 6. Reevalúa la completitud para transicionar a revisión las certificaciones que
     *    quedaron completos al excluir la actividad eliminada.
     *
     * El orden es crítico: ambas eliminaciones deben ocurrir antes del
     * recálculo para que numerador (stamp_activities completadas) y
     * denominador (Activity::count) reflejen el estado final correcto.
     */
    public function deleteActivity(Activity $activity, string $deletedReason, int $deletedBy): bool
    {
        $sectionTypeId = $activity->section?->section_type_id;

        // 1. Eliminar en lote los stamp_activities
        StampActivity::where('activity_id', $activity->id)->delete();

        // 2. Borrar archivos físicos
        if ($activity->file_url) {
            Storage::disk('public')->delete($activity->file_url);
        }
        if ($activity->file_url_content) {
            Storage::disk('public')->delete($activity->file_url_content);
        }

        // 3. Soft-delete de la actividad
        $activity->deleted_reason = $deletedReason;
        $activity->deleted_by = $deletedBy;
        $activity->save();
        $deleted = (bool) $activity->delete();

        // 4-5. Recalcular avance para TODOS los stamps (incluye usuarioes que
        //      nunca entregaron nada para esta actividad: su denominador también
        //      se redujo) y transicionar a revisión los que alcancen el 100%.
        if ($sectionTypeId) {
            $readyForReview = $this->stampService->generateAdvanceBulkForAll($sectionTypeId);
            $this->stampService->reevaluateCompletionForStamps($readyForReview);
        }

        return $deleted;
    }
}
