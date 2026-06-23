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
use App\Services\SectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);

        StampActivityStatus::insert([
            ['id' => 1, 'name' => 'Sin Iniciar',      'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Pendiente',        'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Con Evidencia',    'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'En Revision',      'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Con Observaciones', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Aprobado',         'created_at' => now(), 'updated_at' => now()],
        ]);

        StampStatus::insert([
            ['id' => 1, 'name' => 'Nuevo',       'hexadecimal' => '#9164CC'],
            ['id' => 2, 'name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['id' => 3, 'name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['id' => 4, 'name' => 'Aprobado',    'hexadecimal' => '#00BC70'],
            ['id' => 5, 'name' => 'Vencido',     'hexadecimal' => '#eb551a'],
            ['id' => 6, 'name' => 'Rechazado',   'hexadecimal' => '#FF4438'],
        ]);

        $this->service = $this->app->make(SectionService::class);
    }

    // -----------------------------------------------------------------------
    // destroy — soft-delete de la sección
    // -----------------------------------------------------------------------

    public function test_destroy_soft_deletes_the_section(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();

        $this->service->destroy($section, 'Motivo de prueba', $user->id);

        $this->assertSoftDeleted('sections', ['id' => $section->id]);
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'deleted_reason' => 'Motivo de prueba',
            'deleted_by' => $user->id,
        ]);
    }

    public function test_destroy_returns_true_on_success(): void
    {
        $section = Section::factory()->create();

        $this->assertTrue($this->service->destroy($section, 'Motivo', 1));
    }

    public function test_destroy_works_for_section_without_activities(): void
    {
        $section = Section::factory()->create();

        $this->assertTrue($this->service->destroy($section, 'Motivo', 1));
        $this->assertSoftDeleted('sections', ['id' => $section->id]);
    }

    // -----------------------------------------------------------------------
    // destroy — actividades de la sección
    // -----------------------------------------------------------------------

    public function test_destroy_soft_deletes_all_activities_of_the_section(): void
    {
        $section = Section::factory()->create();
        $activities = Activity::factory()->count(3)->for($section)->create();

        $this->service->destroy($section, 'Motivo', 1);

        foreach ($activities as $activity) {
            $this->assertSoftDeleted('activities', [
                'id' => $activity->id,
                'deleted_reason' => 'Motivo',
                'deleted_by' => 1,
            ]);
        }
    }

    public function test_destroy_does_not_touch_activities_of_other_sections(): void
    {
        $sectionA = Section::factory()->create();
        $sectionB = Section::factory()->create();

        $activityA = Activity::factory()->for($sectionA)->create();
        $activityB = Activity::factory()->for($sectionB)->create();

        $this->service->destroy($sectionA, 'Motivo', 1);

        $this->assertSoftDeleted('activities', ['id' => $activityA->id]);
        $this->assertDatabaseHas('activities', ['id' => $activityB->id, 'deleted_at' => null]);
    }

    // -----------------------------------------------------------------------
    // destroy — stamp_activities en lote
    // -----------------------------------------------------------------------

    public function test_destroy_soft_deletes_all_stamp_activities_of_the_section(): void
    {
        $section = Section::factory()->create();
        $activities = Activity::factory()->count(2)->for($section)->create();
        $stamp = Stamp::factory()->create();

        foreach ($activities as $activity) {
            StampActivity::create([
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
                'stamp_activity_status_id' => 3,
            ]);
        }

        $this->service->destroy($section, 'Motivo', 1);

        foreach ($activities as $activity) {
            $this->assertSoftDeleted('stamp_activities', [
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
            ]);
        }
    }

    public function test_destroy_does_not_touch_stamp_activities_of_other_sections(): void
    {
        $sectionA = Section::factory()->create();
        $sectionB = Section::factory()->create();
        $activityA = Activity::factory()->for($sectionA)->create();
        $activityB = Activity::factory()->for($sectionB)->create();
        $stamp = Stamp::factory()->create();

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activityA->id, 'stamp_activity_status_id' => 3]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activityB->id, 'stamp_activity_status_id' => 3]);

        $this->service->destroy($sectionA, 'Motivo', 1);

        $this->assertDatabaseHas('stamp_activities', ['activity_id' => $activityB->id, 'deleted_at' => null]);
    }

    // -----------------------------------------------------------------------
    // destroy — recálculo de porcentaje
    // -----------------------------------------------------------------------

    public function test_destroy_recalculates_percent_after_removing_section(): void
    {
        // section_type_id=1 -> campo percent_moduls.
        $sectionType = SectionType::factory()->create(['id' => 1]);

        // Sección a eliminar: 2 actividades completadas.
        $sectionToDelete = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $deletedActivities = Activity::factory()->count(2)->for($sectionToDelete)->create();

        // Sección que permanece: 2 actividades, 1 completada.
        $remainingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $remainingActivities = Activity::factory()->count(2)->for($remainingSection)->create();

        $stamp = Stamp::factory()->create(['percent_moduls' => 0, 'percent_evaluations' => 0, 'percent' => 0]);

        // Completadas en la sección a eliminar (status 6).
        foreach ($deletedActivities as $activity) {
            StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activity->id, 'stamp_activity_status_id' => 6]);
        }
        // En la sección que permanece: 1 completada, 1 sin iniciar.
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivities[0]->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivities[1]->id, 'stamp_activity_status_id' => 1]);

        // Al eliminar la sección: queda 1 completada / 2 actividades del tipo = 50 %.
        $this->service->destroy($sectionToDelete, 'Motivo', 1);

        $stamp->refresh();
        $this->assertEquals(50, $stamp->percent_moduls);
        $this->assertEquals(25, $stamp->percent); // (50 + 0) / 2
    }

    public function test_destroy_recalculates_all_affected_stamps_independently(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);

        $sectionToDelete = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $deleted = Activity::factory()->for($sectionToDelete)->create();

        $remainingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $remaining = Activity::factory()->for($remainingSection)->create();

        $stampA = Stamp::factory()->create(['percent_moduls' => 0, 'percent_evaluations' => 0, 'percent' => 0]);
        $stampB = Stamp::factory()->create(['percent_moduls' => 0, 'percent_evaluations' => 0, 'percent' => 0]);

        // stampA: completa la actividad restante.
        StampActivity::create(['stamp_id' => $stampA->id, 'activity_id' => $deleted->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stampA->id, 'activity_id' => $remaining->id, 'stamp_activity_status_id' => 6]);
        // stampB: no completa la actividad restante.
        StampActivity::create(['stamp_id' => $stampB->id, 'activity_id' => $deleted->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stampB->id, 'activity_id' => $remaining->id, 'stamp_activity_status_id' => 1]);

        $this->service->destroy($sectionToDelete, 'Motivo', 1);

        // Queda 1 actividad del tipo.
        $this->assertEquals(100, $stampA->fresh()->percent_moduls); // 1/1
        $this->assertEquals(0, $stampB->fresh()->percent_moduls);   // 0/1
    }

    public function test_destroy_sets_percent_to_zero_when_no_activities_of_type_remain(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $activity = Activity::factory()->for($section)->create();

        $stamp = Stamp::factory()->create(['percent_moduls' => 100, 'percent_evaluations' => 0, 'percent' => 50]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activity->id, 'stamp_activity_status_id' => 6]);

        $this->service->destroy($section, 'Motivo', 1);

        // Sin actividades del tipo: 0 %.
        $this->assertEquals(0, $stamp->fresh()->percent_moduls);
    }

    // -----------------------------------------------------------------------
    // destroy — reevaluación de completitud (transición a revisión)
    // -----------------------------------------------------------------------

    public function test_destroy_sends_stamp_to_review_when_remaining_activities_are_all_complete(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);

        // Sección con la actividad rechazada (la que mantiene atascado al proveedor).
        $blockingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $blockingActivity = Activity::factory()->for($blockingSection)->create();

        // Sección que permanece, con todas sus actividades enviadas.
        $remainingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $remainingActivities = Activity::factory()->count(2)->for($remainingSection)->create();

        // Proveedor aún editando (Nuevo) sin revisor asignado.
        $stamp = Stamp::factory()->create(['stamp_status_id' => 1, 'reviewer_id' => null]);

        // Actividad bloqueante: con observaciones (status 5).
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $blockingActivity->id, 'stamp_activity_status_id' => 5]);
        // Restantes: con evidencia (3) y aprobada (6).
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivities[0]->id, 'stamp_activity_status_id' => 3]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivities[1]->id, 'stamp_activity_status_id' => 6]);

        // Al eliminar la sección bloqueante, todo lo restante está enviado -> Por Revisar.
        $this->service->destroy($blockingSection, 'Motivo', 1);

        $stamp->refresh();
        $this->assertEquals(2, $stamp->stamp_status_id); // Por Revisar
        // Las actividades en estatus 3 pasan a 4 (En Revisión).
        $this->assertDatabaseHas('stamp_activities', [
            'stamp_id' => $stamp->id,
            'activity_id' => $remainingActivities[0]->id,
            'stamp_activity_status_id' => 4,
        ]);
    }

    public function test_destroy_sends_to_en_revision_when_reviewer_assigned(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);

        $blockingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $blockingActivity = Activity::factory()->for($blockingSection)->create();

        $remainingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $remainingActivity = Activity::factory()->for($remainingSection)->create();

        $reviewer = User::factory()->create();
        // Proveedor rechazado (corrigiendo) con revisor asignado.
        $stamp = Stamp::factory()->create(['stamp_status_id' => 6, 'reviewer_id' => $reviewer->id]);

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $blockingActivity->id, 'stamp_activity_status_id' => 5]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivity->id, 'stamp_activity_status_id' => 6]);

        $this->service->destroy($blockingSection, 'Motivo', 1);

        $this->assertEquals(3, $stamp->fresh()->stamp_status_id); // En Revisión
    }

    public function test_destroy_does_not_send_to_review_when_activities_still_pending(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);

        $blockingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $blockingActivity = Activity::factory()->for($blockingSection)->create();

        $remainingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $remainingActivities = Activity::factory()->count(2)->for($remainingSection)->create();

        $stamp = Stamp::factory()->create(['stamp_status_id' => 1, 'reviewer_id' => null]);

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $blockingActivity->id, 'stamp_activity_status_id' => 5]);
        // Una restante aún sin iniciar (status 1) -> no completo.
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivities[0]->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivities[1]->id, 'stamp_activity_status_id' => 1]);

        $this->service->destroy($blockingSection, 'Motivo', 1);

        $this->assertEquals(1, $stamp->fresh()->stamp_status_id); // Sigue Nuevo
    }

    public function test_destroy_does_not_regress_already_approved_stamp(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);

        $blockingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $blockingActivity = Activity::factory()->for($blockingSection)->create();

        $remainingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $remainingActivity = Activity::factory()->for($remainingSection)->create();

        // Sello ya Aprobado: no debe regresar a Por Revisar.
        $stamp = Stamp::factory()->create(['stamp_status_id' => 4, 'reviewer_id' => null]);

        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $blockingActivity->id, 'stamp_activity_status_id' => 5]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $remainingActivity->id, 'stamp_activity_status_id' => 6]);

        $this->service->destroy($blockingSection, 'Motivo', 1);

        $this->assertEquals(4, $stamp->fresh()->stamp_status_id); // Permanece Aprobado
    }

    // -----------------------------------------------------------------------
    // destroy — archivos de storage
    // -----------------------------------------------------------------------

    /**
     * NUEVO CASO 1: el proveedor nunca entregó nada para las actividades eliminadas.
     * Su stamp no tiene stamp_activities para esas actividades, por lo que
     * el código anterior (basado en $affectedStampIds) jamás lo recalculaba.
     * Al eliminar la sección, el denominador se reduce y el porcentaje debe subir.
     */
    public function test_destroy_recalculates_stamps_that_never_submitted_deleted_activities(): void
    {
        $sectionType = SectionType::factory()->create(['id' => 1]);

        // Sección a eliminar: 1 actividad que el proveedor NUNCA tocó (sin stamp_activity).
        $sectionToDelete = Section::factory()->create(['section_type_id' => $sectionType->id]);
        Activity::factory()->for($sectionToDelete)->create();

        // Sección que permanece: 4 actividades, todas completadas por el proveedor.
        $remainingSection = Section::factory()->create(['section_type_id' => $sectionType->id]);
        $remainingActivities = Activity::factory()->count(4)->for($remainingSection)->create();

        $stamp = Stamp::factory()->create(['percent_moduls' => 80, 'percent_evaluations' => 0, 'percent' => 40]);
        foreach ($remainingActivities as $act) {
            StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $act->id, 'stamp_activity_status_id' => 6]);
        }

        // La actividad de sectionToDelete no tiene stamp_activity.
        // Al eliminar: denominador baja de 5 → 4. Completadas: 4/4 = 100 %.
        $this->service->destroy($sectionToDelete, 'Motivo', 1);

        $this->assertEquals(100, $stamp->fresh()->percent_moduls);
    }

    /**
     * NUEVO CASO 2: ambos tipos alcanzan el 100% tras la eliminación.
     * El proveedor tenía módulos al 100% y protocolos al 50% (1/2).
     * Al eliminar la sección de protocolos con la actividad que nunca tocó,
     * protocolos pasa a 1/1 = 100% → ambos tipos completos → Por Revisar.
     */
    public function test_destroy_transitions_stamp_to_review_when_all_types_complete_after_deletion(): void
    {
        $typeModuls = SectionType::factory()->create(['id' => 1]);
        $typeEvals = SectionType::factory()->create(['id' => 2]);

        // Módulos: 2 actividades, ambas completadas.
        $modulSection = Section::factory()->create(['section_type_id' => $typeModuls->id]);
        $modulActivities = Activity::factory()->count(2)->for($modulSection)->create();

        // Protocolos completados: 1 actividad entregada.
        $evalDoneSection = Section::factory()->create(['section_type_id' => $typeEvals->id]);
        $evalDoneActivity = Activity::factory()->for($evalDoneSection)->create();

        // Sección a eliminar: 1 actividad de protocolos que el proveedor NUNCA tocó.
        $evalDeleteSection = Section::factory()->create(['section_type_id' => $typeEvals->id]);
        Activity::factory()->for($evalDeleteSection)->create();

        $stamp = Stamp::factory()->create([
            'stamp_status_id' => 1,
            'reviewer_id' => null,
            'percent_moduls' => 100,
            'percent_evaluations' => 50,
            'percent' => 75,
        ]);

        foreach ($modulActivities as $act) {
            StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $act->id, 'stamp_activity_status_id' => 6]);
        }
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $evalDoneActivity->id, 'stamp_activity_status_id' => 6]);
        // La actividad de evalDeleteSection NO tiene stamp_activity.

        // Al eliminar: protocolos pasa de 1/2 (50%) a 1/1 (100%) → ambos al 100% → Por Revisar.
        $this->service->destroy($evalDeleteSection, 'Motivo', 1);

        $stamp->refresh();
        $this->assertEquals(100, $stamp->percent_evaluations);
        $this->assertEquals(2, $stamp->stamp_status_id); // Por Revisar
    }

    // -----------------------------------------------------------------------
    // destroy — archivos de storage
    // -----------------------------------------------------------------------

    public function test_destroy_removes_logo_and_content_files_of_activities(): void
    {
        Storage::fake('public');

        $logoPath = UploadedFile::fake()->image('logo.jpg')->store('activities', 'public');
        $contentPath = UploadedFile::fake()->create('doc.pdf', 50)->store('activities', 'public');

        $section = Section::factory()->create();
        Activity::factory()->for($section)->create([
            'file_url' => $logoPath,
            'file_url_content' => $contentPath,
        ]);

        $this->service->destroy($section, 'Motivo', 1);

        Storage::disk('public')->assertMissing($logoPath);
        Storage::disk('public')->assertMissing($contentPath);
    }
}
