<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\AuthMethod;
use App\Models\Section;
use App\Models\SectionType;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityStatus;
use App\Models\StampStatus;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);

        StampActivityStatus::insert([
            ['id' => 1, 'name' => 'Sin Iniciar',        'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Pendiente',           'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Con Evidencia',       'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'En Revision',         'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Con Observaciones',   'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Aprobado',            'created_at' => now(), 'updated_at' => now()],
        ]);

        StampStatus::insert([
            ['id' => 1, 'name' => 'Nuevo',       'hexadecimal' => '#9164CC'],
            ['id' => 2, 'name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['id' => 3, 'name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['id' => 4, 'name' => 'Aprobado',    'hexadecimal' => '#00BC70'],
            ['id' => 5, 'name' => 'Vencido',     'hexadecimal' => '#FF4438'],
        ]);

        $this->service = $this->app->make(ActivityService::class);
    }

    // -----------------------------------------------------------------------
    // deleteActivity — soft-delete
    // -----------------------------------------------------------------------

    public function test_delete_activity_soft_deletes_the_activity(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        $this->service->deleteActivity($activity, 'Motivo de prueba', $user->id);

        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'deleted_reason' => 'Motivo de prueba',
            'deleted_by' => $user->id,
        ]);
    }

    public function test_delete_activity_returns_true_on_success(): void
    {
        $activity = Activity::factory()->create();

        $result = $this->service->deleteActivity($activity, 'Motivo', 1);

        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // deleteActivity — stamp_activities en lote
    // -----------------------------------------------------------------------

    public function test_delete_activity_soft_deletes_all_related_stamp_activities(): void
    {
        $section = Section::factory()->create();
        $activity = Activity::factory()->for($section)->create();

        $stamps = Stamp::factory()->count(3)->create();
        foreach ($stamps as $stamp) {
            StampActivity::create([
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
                'stamp_activity_status_id' => 3,
            ]);
        }

        $this->service->deleteActivity($activity, 'Motivo', 1);

        // Los tres stamp_activities deben estar soft-deleted
        foreach ($stamps as $stamp) {
            $this->assertSoftDeleted('stamp_activities', [
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
            ]);
        }
    }

    public function test_delete_activity_does_not_touch_stamp_activities_of_other_activities(): void
    {
        $section = Section::factory()->create();
        $activityA = Activity::factory()->for($section)->create();
        $activityB = Activity::factory()->for($section)->create();
        $stamp = Stamp::factory()->create();

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activityA->id, 'stamp_activity_status_id' => 3]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activityB->id, 'stamp_activity_status_id' => 3]);

        $this->service->deleteActivity($activityA, 'Motivo', 1);

        // activityB stamp_activity debe permanecer intacta
        $this->assertDatabaseHas('stamp_activities', [
            'activity_id' => $activityB->id,
            'deleted_at' => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // deleteActivity — recálculo de porcentaje
    // -----------------------------------------------------------------------

    public function test_delete_activity_recalculates_percent_after_removing_stamp_activities(): void
    {
        // section_type_id=1 -> campo percent_moduls
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);

        // 3 actividades en esta sección, stamp con 2 completadas (status 6)
        $activities = Activity::factory()->count(3)->for($section)->create();
        $stamp = Stamp::factory()->create(['percent_moduls' => 0, 'percent_evaluations' => 0, 'percent' => 0]);

        // Actividad 0: completada (status 6)
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 6]);
        // Actividad 1: completada (status 6)
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 6]);
        // Actividad 2: sin evidencia (status 1)
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[2]->id, 'stamp_activity_status_id' => 1]);

        // Eliminar actividad[0] (completada): el stamp_activity desaparece y la actividad ya no existe
        // Esperado: 1 completada / 2 actividades totales = 50 %
        $this->service->deleteActivity($activities[0], 'Motivo', 1);

        $stamp->refresh();
        $this->assertEquals(50, $stamp->percent_moduls);
        $this->assertEquals(25, $stamp->percent); // (50 + 0) / 2
    }

    public function test_delete_activity_sets_percent_to_zero_when_no_completed_stamp_activities_remain(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);

        $activities = Activity::factory()->count(2)->for($section)->create();
        $stamp = Stamp::factory()->create(['percent_moduls' => 50, 'percent_evaluations' => 0, 'percent' => 25]);

        // Solo la actividad a eliminar tiene stamp_activity completada
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 1]);

        $this->service->deleteActivity($activities[0], 'Motivo', 1);

        $stamp->refresh();
        // 0 completadas / 1 actividad restante = 0 %
        $this->assertEquals(0, $stamp->percent_moduls);
    }

    public function test_delete_activity_skips_recalculation_when_no_stamps_affected(): void
    {
        // Actividad sin ningún stamp_activity asociado
        $activity = Activity::factory()->create();

        // No debe lanzar ninguna excepción
        $result = $this->service->deleteActivity($activity, 'Motivo', 1);

        $this->assertTrue($result);
    }

    public function test_delete_activity_recalculates_all_affected_stamps_independently(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);

        // 2 actividades, 2 stamps distintos
        $activities = Activity::factory()->count(2)->for($section)->create();
        $stampA = Stamp::factory()->create(['percent_moduls' => 0, 'percent_evaluations' => 0, 'percent' => 0]);
        $stampB = Stamp::factory()->create(['percent_moduls' => 0, 'percent_evaluations' => 0, 'percent' => 0]);

        // stampA: ambas actividades completadas (status 6)
        StampActivity::create(['stamp_id' => $stampA->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stampA->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 6]);
        // stampB: solo actividad[1] completada
        StampActivity::create(['stamp_id' => $stampB->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 1]);
        StampActivity::create(['stamp_id' => $stampB->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 6]);

        // Eliminar activities[0]: 1 actividad queda
        // stampA: 1 completada / 1 total = 100 %
        // stampB: 1 completada / 1 total = 100 %
        $this->service->deleteActivity($activities[0], 'Motivo', 1);

        $this->assertEquals(100, $stampA->fresh()->percent_moduls);
        $this->assertEquals(100, $stampB->fresh()->percent_moduls);
    }

    // -----------------------------------------------------------------------
    // deleteActivity — reevaluación de completitud (transición a revisión)
    // -----------------------------------------------------------------------

    public function test_delete_activity_sends_stamp_to_review_when_remaining_are_all_complete(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $activities = Activity::factory()->count(3)->for($section)->create();

        // Proveedor aún editando, sin revisor.
        $stamp = Stamp::factory()->create(['stamp_status_id' => 1, 'reviewer_id' => null]);

        // Actividad bloqueante con observaciones (5) + dos enviadas (3 y 6).
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 5]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 3]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[2]->id, 'stamp_activity_status_id' => 6]);

        // Al eliminar la actividad bloqueante, lo restante queda completo -> Por Revisar.
        $this->service->deleteActivity($activities[0], 'Motivo', 1);

        $this->assertEquals(2, $stamp->fresh()->stamp_status_id);
    }

    public function test_delete_activity_does_not_send_to_review_when_pending_remains(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $activities = Activity::factory()->count(3)->for($section)->create();

        $stamp = Stamp::factory()->create(['stamp_status_id' => 1, 'reviewer_id' => null]);

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 5]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[2]->id, 'stamp_activity_status_id' => 1]);

        $this->service->deleteActivity($activities[0], 'Motivo', 1);

        $this->assertEquals(1, $stamp->fresh()->stamp_status_id);
    }

    public function test_delete_activity_does_not_regress_in_review_stamp(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $activities = Activity::factory()->count(2)->for($section)->create();

        // Sello ya En Revisión (status 3): no debe re-disparar transición.
        $stamp = Stamp::factory()->create(['stamp_status_id' => 3, 'reviewer_id' => null]);

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 5]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 6]);

        $this->service->deleteActivity($activities[0], 'Motivo', 1);

        $this->assertEquals(3, $stamp->fresh()->stamp_status_id);
    }

    // -----------------------------------------------------------------------
    // deleteActivity — archivos de storage
    // -----------------------------------------------------------------------

    /**
     * NUEVO CASO 1: el proveedor nunca entregó nada para la actividad eliminada.
     * Su stamp no tiene stamp_activity para esa actividad, por lo que el código
     * anterior (basado en $affectedStampIds) jamás lo recalculaba.
     * Al eliminar la actividad, el denominador se reduce y el porcentaje debe subir.
     */
    public function test_delete_activity_recalculates_stamps_that_never_submitted_the_deleted_activity(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);

        // 3 actividades: proveedor completó las 2 primeras, la tercera NUNCA la tocó.
        $activities = Activity::factory()->count(3)->for($section)->create();
        $stamp = Stamp::factory()->create(['percent_moduls' => 0, 'percent_evaluations' => 0, 'percent' => 0]);

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[0]->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activities[1]->id, 'stamp_activity_status_id' => 6]);
        // $activities[2] no tiene stamp_activity (nunca tocada).

        // Al eliminar activities[2]: denominador baja de 3 → 2. Completadas: 2/2 = 100 %.
        $this->service->deleteActivity($activities[2], 'Motivo', 1);

        $this->assertEquals(100, $stamp->fresh()->percent_moduls);
    }

    /**
     * NUEVO CASO 2: ambos tipos alcanzan el 100% tras la eliminación.
     * El proveedor tenía módulos al 100% y protocolos al 50% (1/2).
     * Al eliminar la actividad de protocolos que nunca tocó,
     * protocolos pasa a 1/1 = 100% → ambos tipos completos → Por Revisar.
     */
    public function test_delete_activity_sends_to_review_when_stamp_never_submitted_deleted_activity(): void
    {
        $typeModuls = SectionType::factory()->create(['id' => 1]);
        $typeEvals = SectionType::factory()->create(['id' => 2]);

        $modulSection = Section::factory()->create(['section_type_id' => $typeModuls->id]);
        $modulActivity = Activity::factory()->for($modulSection)->create();

        $evalSection = Section::factory()->create(['section_type_id' => $typeEvals->id]);
        $evalActivities = Activity::factory()->count(2)->for($evalSection)->create();

        $stamp = Stamp::factory()->create([
            'stamp_status_id' => 1,
            'reviewer_id' => null,
            'percent_moduls' => 100,
            'percent_evaluations' => 50,
            'percent' => 75,
        ]);

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $modulActivity->id,       'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $evalActivities[0]->id, 'stamp_activity_status_id' => 6]);
        // $evalActivities[1] sin stamp_activity (nunca tocada).

        // Al eliminar evalActivities[1]: protocolos pasa de 1/2 (50%) a 1/1 (100%)
        // → ambos tipos al 100% → Por Revisar.
        $this->service->deleteActivity($evalActivities[1], 'Motivo', 1);

        $this->assertEquals(2, $stamp->fresh()->stamp_status_id); // Por Revisar
    }

    // -----------------------------------------------------------------------
    // deleteActivity — archivos de storage
    // -----------------------------------------------------------------------

    public function test_delete_activity_removes_logo_and_content_files(): void
    {
        Storage::fake('public');

        $logoPath = UploadedFile::fake()->image('logo.jpg')->store('activities', 'public');
        $contentPath = UploadedFile::fake()->create('doc.pdf', 50)->store('activities', 'public');

        $activity = Activity::factory()->create([
            'file_url' => $logoPath,
            'file_url_content' => $contentPath,
        ]);

        $this->service->deleteActivity($activity, 'Motivo', 1);

        Storage::disk('public')->assertMissing($logoPath);
        Storage::disk('public')->assertMissing($contentPath);
    }

    public function test_delete_activity_works_when_activity_has_no_files(): void
    {
        $activity = Activity::factory()->create(['file_url' => null, 'file_url_content' => null]);

        // No debe lanzar ninguna excepción
        $result = $this->service->deleteActivity($activity, 'Motivo', 1);

        $this->assertTrue($result);
    }
}
