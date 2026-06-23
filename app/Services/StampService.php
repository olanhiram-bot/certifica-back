<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Stamp;
use App\Models\StampActivity;
use Illuminate\Support\Facades\DB;

class StampService
{
    private NotificationService $notificationService;
    private StampValidationService $validationService;

    public function __construct(NotificationService $notificationService, StampValidationService $validationService)
    {
        $this->notificationService = $notificationService;
        $this->validationService = $validationService;
    }

    private function getSectionByStampActivity(StampActivity $stampActivity): ?int
    {
        return $stampActivity->activity?->section?->section_type_id;
    }

    public function isSectionComplete(StampActivity $stampActivity): bool
    {
        $stamp = $stampActivity->stamp;
        if (! $stamp) {
            return false;
        }

        $this->validationService->validateStampCurrent($stamp);

        return $this->evaluateStampCompletion($stamp);
    }

    /**
     * Evalúa si la certificacion tiene todas sus actividades requeridas enviadas
     * (estatus 3, 4 o 6) y, de ser así, lo transiciona a revisión.
     *
     * Excluye automáticamente actividades y stamp_activities eliminadas
     * (soft-delete), por lo que el conteo refleja siempre el estado vigente.
     */
    private function evaluateStampCompletion(Stamp $stamp): bool
    {
        // Base de actividades requeridas (módulos y evaluaciones).
        $baseQuery = Activity::whereHas('section', function ($query) {
            $query->whereIn('section_type_id', [1, 2]);
        });

        $activityIds = $baseQuery->pluck('id')->toArray();
        $totalActivities = count($activityIds);

        if ($totalActivities === 0) {
            return false;
        }

        // Realizadas con éxito (Estatus 3,4,6) para esta certificacion.
        $completedActivities = (clone $baseQuery)->whereHas('stampsActivity', function ($query) use ($stamp) {
            $query->whereIn('stamp_activity_status_id', [3, 4, 6])
                ->where('stamp_id', $stamp->id);
        })->count();

        if ($totalActivities === $completedActivities) {
            $this->sendAllToReview($stamp, $activityIds);

            return true;
        }

        return false;
    }

    /**
     * Reevalúa la completitud de varias certificaciones tras eliminar actividades/secciones.
     * Si, sin considerar lo eliminado, una certificacion quedó con todas sus actividades
     * enviadas, lo transiciona a revisión igual que addFile.
     *
     * Solo aplica a certificaciones que el usuario aún está editando (Nuevo o Rechazado);
     * nunca regresa una certificacion ya en revisión, aprobado o vencido.
     *
     * @param  int[]  $stampIds
     */
    public function reevaluateCompletionForStamps(array $stampIds): void
    {
        if (empty($stampIds)) {
            return;
        }

        Stamp::whereIn('id', $stampIds)
            ->whereIn('stamp_status_id', [1, 6]) // 1: Nuevo, 6: Rechazado
            ->get()
            ->each(fn (Stamp $stamp) => $this->evaluateStampCompletion($stamp));
    }

    /**
     * Update all stamp activities status to 'in review'.
     * Accept null to be defensive when callers may pass null.
     *
     * @param  int[]  $activityIds  Array of activity ids to restrict the update to
     */
    private function sendAllToReview(?Stamp $stamp, array $activityIds): void
    {
        if (! $stamp) {
            return;
        }

        // Validar si el expediente ya fue tomado
        if ($stamp->reviewer_id !== null) {
            $stamp->stamp_status_id = 3; // En Revisión
            // se envía notificación al revizador que el expediente ya se encuentra listo para revisión
            $this->notificationService->sendStampNotification($stamp, 5);

            $stamp->take_review_date = now();
        } else {
            $stamp->stamp_status_id = 2; // Por Revisar
            // se envía notificación al revizador que el expediente ya se encuentra listo para revisión
            $this->notificationService->sendStampNotification($stamp, 1);
        }
        $stamp->save();

        $stamp->stampsActivity()->where('stamp_activity_status_id', 3)->whereIn('activity_id', $activityIds)->update([
            'stamp_activity_status_id' => 4,
        ]);
    }

    public function generateAdvance(StampActivity $stampActivity): void
    {
        $stamp = Stamp::find($stampActivity->stamp_id);
        if (! $stamp) {
            return;
        }

        $sectionTypeId = $this->getSectionByStampActivity($stampActivity);
        if ($sectionTypeId === null) {
            return;
        }

        $totalActivity = Activity::whereHas('section', function ($q) use ($sectionTypeId) {
            $q->where('section_type_id', $sectionTypeId);
        })->count();

        $totalEndActivity = $stamp->stampsActivity()
            ->whereIn('stamp_activity_status_id', [3, 4, 6])
            ->whereHas('activity.section', function ($q) use ($sectionTypeId) {
                $q->where('section_type_id', $sectionTypeId);
            })
            ->count();

        $percent = $totalActivity > 0
            ? ($totalEndActivity * 100) / $totalActivity
            : 0;

        $field = $sectionTypeId == 1 ? 'percent_moduls' : 'percent_evaluations';
        $stamp->{$field} = (int) $percent;
        $stamp->percent = (int) (($stamp->percent_moduls + $stamp->percent_evaluations) / 2);

        $stamp->save();

    }

    /**
     * Recalcula el avance de múltiples stamps para un mismo section_type_id.
     * Más eficiente que llamar generateAdvance N veces: Activity::count() se ejecuta una sola vez
     * y los stamps se cargan en una sola query con whereIn.
     *
     * @param  int[]  $stampIds
     */
    public function generateAdvanceBulk(array $stampIds, int $sectionTypeId): void
    {
        if (empty($stampIds)) {
            return;
        }

        $totalActivity = Activity::whereHas('section', function ($q) use ($sectionTypeId) {
            $q->where('section_type_id', $sectionTypeId);
        })->count();

        $field = $sectionTypeId == 1 ? 'percent_moduls' : 'percent_evaluations';

        Stamp::whereIn('id', $stampIds)->each(function (Stamp $stamp) use ($sectionTypeId, $totalActivity, $field) {
            $totalEndActivity = $stamp->stampsActivity()
                ->whereIn('stamp_activity_status_id', [3, 4, 6])
                ->whereHas('activity.section', function ($q) use ($sectionTypeId) {
                    $q->where('section_type_id', $sectionTypeId);
                })
                ->count();

            $percent = $totalActivity > 0
                ? ($totalEndActivity * 100) / $totalActivity
                : 0;

            $stamp->{$field} = (int) $percent;
            $stamp->percent = (int) (($stamp->percent_moduls + $stamp->percent_evaluations) / 2);
            $stamp->save();
        });

    }

    /**
     * Recalcula el avance de TODOS los stamps no eliminados para un section_type.
     *
     * A diferencia de generateAdvanceBulk, cubre también los usuarioes que nunca
     * entregaron nada para las actividades eliminadas: su denominador también se
     * reduce y su porcentaje real aumenta, pero no dejan rastro en stamp_activities,
     * por lo que quedarían fuera de $affectedStampIds.
     *
     * Estrategia (compatible con MySQL y SQLite):
     *  1. Una query agrega el conteo de completadas por stamp → diccionario.
     *  2. Los stamps se actualizan en chunks de 100 para acotar el uso de memoria.
     *  3. Una segunda query agrega las completadas de ambos tipos para identificar
     *     los stamps editables que ahora cumplen la condición de completitud total.
     *
     * Para bases de datos de gran volumen en producción (MySQL), el paso 2 puede
     * optimizarse con un UPDATE … LEFT JOIN en una sola query. La implementación
     * actual es intencionalmente compatible con múltiples motores.
     *
     * @return int[] IDs de stamps en estado editable (Nuevo/Rechazado) donde ahora
     *               TODAS las actividades vigentes están enviadas/aprobadas.
     */
    public function generateAdvanceBulkForAll(int $sectionTypeId): array
    {
        $field = $sectionTypeId === 1 ? 'percent_moduls' : 'percent_evaluations';

        $totalActivity = Activity::whereHas('section', function ($q) use ($sectionTypeId) {
            $q->where('section_type_id', $sectionTypeId);
        })->count();

        // ── 1. Conteo de completadas por stamp para este tipo (una sola query) ──
        // Stamps sin entregas en este tipo no aparecen → se tratan como 0 (??).
        $cntByStamp = $totalActivity > 0
            ? DB::table('stamp_activities as sa')
                ->join('activities as a', function ($j) {
                    $j->on('a.id', '=', 'sa.activity_id')->whereNull('a.deleted_at');
                })
                ->join('sections as sec', function ($j) use ($sectionTypeId) {
                    $j->on('sec.id', '=', 'a.section_id')
                        ->whereNull('sec.deleted_at')
                        ->where('sec.section_type_id', $sectionTypeId);
                })
                ->whereIn('sa.stamp_activity_status_id', [3, 4, 6])
                ->whereNull('sa.deleted_at')
                ->select('sa.stamp_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('sa.stamp_id')
                ->pluck('cnt', 'stamp_id')
                ->toArray()
            : [];

        // ── 2. Actualización en chunks (memoria acotada a 100 stamps a la vez) ──
        Stamp::whereNull('deleted_at')
            ->select(['id', 'percent_moduls', 'percent_evaluations', 'percent'])
            ->chunkById(100, function ($stamps) use ($field, $totalActivity, $cntByStamp) {
                foreach ($stamps as $stamp) {
                    $cnt = $cntByStamp[$stamp->id] ?? 0;
                    $stamp->{$field} = $totalActivity > 0 ? (int) ($cnt * 100 / $totalActivity) : 0;
                    $stamp->percent = (int) (($stamp->percent_moduls + $stamp->percent_evaluations) / 2);
                    $stamp->save();
                }
            });

        // ── 3. Identificar stamps editables donde TODAS las actividades vigentes
        //       (tipos 1 y 2) están enviadas/aprobadas (misma lógica que evaluateStampCompletion).
        $totalAll = Activity::whereHas('section', function ($q) {
            $q->whereIn('section_type_id', [1, 2]);
        })->count();

        if ($totalAll === 0) {
            return [];
        }

        $completedAllByStamp = DB::table('stamp_activities as sa')
            ->join('activities as a', function ($j) {
                $j->on('a.id', '=', 'sa.activity_id')->whereNull('a.deleted_at');
            })
            ->join('sections as sec', function ($j) {
                $j->on('sec.id', '=', 'a.section_id')
                    ->whereNull('sec.deleted_at')
                    ->whereIn('sec.section_type_id', [1, 2]);
            })
            ->whereIn('sa.stamp_activity_status_id', [3, 4, 6])
            ->whereNull('sa.deleted_at')
            ->select('sa.stamp_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('sa.stamp_id')
            ->pluck('cnt', 'stamp_id')
            ->toArray();

        return Stamp::whereNull('deleted_at')
            ->whereIn('stamp_status_id', [1, 6])
            ->pluck('id')
            ->filter(fn ($id) => ($completedAllByStamp[$id] ?? 0) === $totalAll)
            ->values()
            ->all();
    }

    public function validateStampCurrent(Stamp $stamp): void
    {
        $this->validationService->validateStampCurrent($stamp);
    }

    public function validateDateUploadEvidence(StampActivity $stampActivity): void
    {
        $this->validationService->validateDateUploadEvidence($stampActivity);
    }

    public function validateDateEvaluate(Stamp $stamp): void
    {
        $this->validationService->validateDateEvaluate($stamp);
    }

    public function isReviewComplete(StampActivity $stampActivity): bool
    {
        $stamp = $stampActivity->stamp;

        if (! $stamp) {
            return false;
        }

        $totalActivities = Activity::whereHas('section', function ($query) {
            $query->whereIn('section_type_id', [1, 2]);
        })->count();

        if ($totalActivities === 0) {
            return false;
        }

        $reviewedActivities = $stamp->stampsActivity()
            ->whereIn('stamp_activity_status_id', [5, 6])
            ->whereHas('activity.section', function ($query) {
                $query->whereIn('section_type_id', [1, 2]);
            })
            ->count();

        return $totalActivities === $reviewedActivities;
    }

    public function approveStamp(Stamp $stamp): void
    {
        // TODO: Consumir API de usuarioes
        $stamp->update([
            'stamp_status_id' => 4,
            'is_active' => true,
        ]);

        // se envía notificación al usuario que el expediente fue aprobado
        $this->notificationService->sendStampNotification($stamp, 3);
    }

    public function rejectStamp(Stamp $stamp): void
    {
        $stamp->update([
            'reject_date' => now(),
            'stamp_status_id' => 6,
        ]);
        // se envía notificación al usuario que el expediente fue rechazado
        $this->notificationService->sendStampNotification($stamp, 4);
    }
}
