<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\AuthMethod;
use App\Models\Section;
use App\Models\SectionType;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Models\StampActivityStatus;
use App\Models\StampStatus;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.dias_prov_subir_todo' => 10]);

        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);

        SectionType::insert([
            ['id' => 1, 'name' => 'Módulos', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Protocolos', 'created_at' => now(), 'updated_at' => now()],
        ]);

        StampStatus::insert([
            ['id' => 1, 'name' => 'Nuevo', 'hexadecimal' => '#9164CC'],
            ['id' => 2, 'name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['id' => 3, 'name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['id' => 4, 'name' => 'Aprobado', 'hexadecimal' => '#00BC70'],
            ['id' => 5, 'name' => 'Vencido', 'hexadecimal' => '#eb551a'],
            ['id' => 6, 'name' => 'Rechazado', 'hexadecimal' => '#FF4438'],
        ]);

        $this->service = new DashboardService;
    }

    private function provider(): User
    {
        return User::factory()->create(['auth_method_id' => 1]);
    }

    // -----------------------------------------------------------------------
    // getResume — sin stamp
    // -----------------------------------------------------------------------

    public function test_get_resume_returns_correct_keys(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $result = $this->service->getResume();

        $this->assertArrayHasKey('days_remaining', $result);
        $this->assertArrayHasKey('comments', $result);
        $this->assertArrayHasKey('evidences_to_upload', $result);
        $this->assertArrayHasKey('protocols_to_upload', $result);
    }

    public function test_get_resume_returns_10_days_remaining_when_no_stamp(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $result = $this->service->getResume();

        $this->assertSame(10, $result['days_remaining']);
    }

    public function test_get_resume_returns_zero_comments_when_no_stamp(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $result = $this->service->getResume();

        $this->assertSame(0, $result['comments']);
    }

    public function test_get_resume_evidences_to_upload_equals_total_type1_activities_when_no_stamp(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $section = Section::factory()->create(['section_type_id' => 1]);
        Activity::factory()->count(4)->create(['section_id' => $section->id]);

        $result = $this->service->getResume();

        $this->assertSame(4, $result['evidences_to_upload']);
    }

    public function test_get_resume_protocols_to_upload_equals_total_type2_activities_when_no_stamp(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $section = Section::factory()->create(['section_type_id' => 2]);
        Activity::factory()->count(3)->create(['section_id' => $section->id]);

        $result = $this->service->getResume();

        $this->assertSame(3, $result['protocols_to_upload']);
    }

    // -----------------------------------------------------------------------
    // getResume — con stamp
    // -----------------------------------------------------------------------

    public function test_get_resume_calculates_days_remaining_from_stamp_creation_date(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(4),
            'stamp_status_id' => 1,
        ]);

        $result = $this->service->getResume();

        $this->assertSame(6, $result['days_remaining']);
    }

    public function test_get_resume_days_remaining_is_zero_when_stamp_is_older_than_10_days(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(20),
        ]);

        $result = $this->service->getResume();

        $this->assertSame(0, $result['days_remaining']);
    }

    public function test_get_resume_counts_comments_on_authenticated_user_stamp(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $activity = StampActivity::factory()->create(['stamp_id' => $stamp->id]);
        StampActivityComment::factory()->count(3)->create(['stamp_activity_id' => $activity->id]);

        $result = $this->service->getResume();

        $this->assertSame(3, $result['comments']);
    }

    public function test_get_resume_does_not_count_comments_from_other_users_stamps(): void
    {
        $provider = $this->provider();
        $other = $this->provider();
        $this->actingAs($provider);

        $myStamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $otherStamp = Stamp::factory()->create(['provider_id' => $other->id]);

        $myActivity = StampActivity::factory()->create(['stamp_id' => $myStamp->id]);
        $otherActivity = StampActivity::factory()->create(['stamp_id' => $otherStamp->id]);

        StampActivityComment::factory()->count(1)->create(['stamp_activity_id' => $myActivity->id]);
        StampActivityComment::factory()->count(9)->create(['stamp_activity_id' => $otherActivity->id]);

        $result = $this->service->getResume();

        $this->assertSame(1, $result['comments']);
    }

    public function test_get_resume_evidences_discounts_uploaded_type1_activities(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activities = Activity::factory()->count(4)->create(['section_id' => $section->id]);

        // 1 subida con archivo
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activities[0]->id,
            'file_path' => 'uploads/evidencia.pdf',
        ]);
        // 1 sin archivo (no debe descontarse)
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activities[1]->id,
            'file_path' => null,
        ]);

        $result = $this->service->getResume();

        $this->assertSame(3, $result['evidences_to_upload']);
    }

    public function test_get_resume_protocols_discounts_uploaded_type2_activities(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 2]);
        $activities = Activity::factory()->count(3)->create(['section_id' => $section->id]);

        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activities[0]->id,
            'file_path' => 'uploads/protocolo.pdf',
        ]);

        $result = $this->service->getResume();

        $this->assertSame(2, $result['protocols_to_upload']);
    }

    public function test_get_resume_evidences_to_upload_cannot_be_negative(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);

        // Subidas == total → debe ser 0, nunca negativo
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'file_path' => 'uploads/evidencia.pdf',
        ]);

        $result = $this->service->getResume();

        $this->assertSame(0, $result['evidences_to_upload']);
        $this->assertGreaterThanOrEqual(0, $result['evidences_to_upload']);
    }

    public function test_get_resume_uses_only_latest_stamp_of_authenticated_user(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $oldStamp = Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(9),
        ]);
        $newStamp = Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(2),
            'stamp_status_id' => 1,
        ]);

        // Comentarios en el stamp antiguo (no deben contarse)
        $oldActivity = StampActivity::factory()->create(['stamp_id' => $oldStamp->id]);
        StampActivityComment::factory()->count(7)->create(['stamp_activity_id' => $oldActivity->id]);

        // Comentario en el stamp más reciente
        $newActivity = StampActivity::factory()->create(['stamp_id' => $newStamp->id]);
        StampActivityComment::factory()->count(2)->create(['stamp_activity_id' => $newActivity->id]);

        $result = $this->service->getResume();

        $this->assertSame(8, $result['days_remaining']);
        $this->assertSame(2, $result['comments']);
    }

    public function test_get_resume_uploaded_activities_from_other_stamps_are_not_counted(): void
    {
        $provider = $this->provider();
        $other = $this->provider();
        $this->actingAs($provider);

        $myStamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $otherStamp = Stamp::factory()->create(['provider_id' => $other->id]);

        $section = Section::factory()->create(['section_type_id' => 1]);
        $activities = Activity::factory()->count(2)->create(['section_id' => $section->id]);

        // El otro proveedor subió ambas actividades; el authenticado no subió ninguna
        StampActivity::factory()->create([
            'stamp_id' => $otherStamp->id,
            'activity_id' => $activities[0]->id,
            'file_path' => 'uploads/otro.pdf',
        ]);
        StampActivity::factory()->create([
            'stamp_id' => $otherStamp->id,
            'activity_id' => $activities[1]->id,
            'file_path' => 'uploads/otro2.pdf',
        ]);

        $result = $this->service->getResume();

        // El proveedor autenticado aún debe ver 2 evidencias pendientes
        $this->assertSame(2, $result['evidences_to_upload']);
    }

    // -----------------------------------------------------------------------
    // getStatusWithTotalsActivities
    // -----------------------------------------------------------------------

    public function test_get_status_with_totals_returns_array_with_correct_keys(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        StampActivityStatus::factory()->create();

        $result = $this->service->getStatusWithTotalsActivities();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stamp_id', $result[0]);
        $this->assertArrayHasKey('status', $result[0]);
        $this->assertArrayHasKey('total_activities', $result[0]);
    }

    public function test_get_status_with_totals_returns_zero_count_when_no_activities(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        StampActivityStatus::factory()->create();

        $result = $this->service->getStatusWithTotalsActivities();

        $this->assertSame(0, $result[0]['total_activities']);
    }

    public function test_get_status_with_totals_counts_activities_for_authenticated_user_only(): void
    {
        $provider = $this->provider();
        $other = $this->provider();
        $this->actingAs($provider);

        $status = StampActivityStatus::factory()->create();
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);

        $myStamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $otherStamp = Stamp::factory()->create(['provider_id' => $other->id]);

        StampActivity::factory()->create([
            'stamp_id' => $myStamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => $status->id,
        ]);
        StampActivity::factory()->create([
            'stamp_id' => $otherStamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => $status->id,
        ]);

        $result = $this->service->getStatusWithTotalsActivities();

        $statusRow = collect($result)->firstWhere('stamp_id', $status->id);
        $this->assertSame(1, $statusRow['total_activities']);
    }

    public function test_get_status_with_totals_filters_by_section_type_id(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $status = StampActivityStatus::factory()->create();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section1 = Section::factory()->create(['section_type_id' => 1]);
        $section2 = Section::factory()->create(['section_type_id' => 2]);
        $activity1 = Activity::factory()->create(['section_id' => $section1->id]);
        $activity2 = Activity::factory()->create(['section_id' => $section2->id]);

        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity1->id,
            'stamp_activity_status_id' => $status->id,
        ]);
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity2->id,
            'stamp_activity_status_id' => $status->id,
        ]);

        $result = $this->service->getStatusWithTotalsActivities(['section_type_id' => 1]);

        $statusRow = collect($result)->firstWhere('stamp_id', $status->id);
        $this->assertSame(1, $statusRow['total_activities']);
    }

    public function test_get_status_with_totals_filters_by_search(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $status = StampActivityStatus::factory()->create();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $matching = Activity::factory()->create(['section_id' => $section->id, 'name' => 'Actividad visible']);
        $noMatch = Activity::factory()->create(['section_id' => $section->id, 'name' => 'Otra actividad']);

        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $matching->id,
            'stamp_activity_status_id' => $status->id,
        ]);
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $noMatch->id,
            'stamp_activity_status_id' => $status->id,
        ]);

        $result = $this->service->getStatusWithTotalsActivities(['search' => 'visible']);

        $statusRow = collect($result)->firstWhere('stamp_id', $status->id);
        $this->assertSame(1, $statusRow['total_activities']);
    }

    public function test_get_status_with_totals_returns_status_name(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $status = StampActivityStatus::factory()->create(['name' => 'Pendiente']);

        $result = $this->service->getStatusWithTotalsActivities();

        $statusRow = collect($result)->firstWhere('stamp_id', $status->id);
        $this->assertSame('Pendiente', $statusRow['status']);
    }

    // -----------------------------------------------------------------------
    // getStampActivities
    // -----------------------------------------------------------------------

    public function test_get_stamp_activities_returns_empty_array_when_no_stamp(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $result = $this->service->getStampActivities();

        $this->assertSame([], $result);
    }

    public function test_get_stamp_activities_returns_paginator_when_stamp_exists(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);
        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $activity->id]);

        $result = $this->service->getStampActivities();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_get_stamp_activities_returns_only_authenticated_user_activities(): void
    {
        $provider = $this->provider();
        $other = $this->provider();
        $this->actingAs($provider);

        $myStamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $otherStamp = Stamp::factory()->create(['provider_id' => $other->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);

        StampActivity::factory()->create(['stamp_id' => $myStamp->id, 'activity_id' => $activity->id]);
        StampActivity::factory()->count(4)->create(['stamp_id' => $otherStamp->id, 'activity_id' => $activity->id]);

        $result = $this->service->getStampActivities();

        $this->assertSame(1, $result->total());
    }

    public function test_get_stamp_activities_uses_only_latest_stamp(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);

        $oldStamp = Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(5),
        ]);
        $newStamp = Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(1),
        ]);

        StampActivity::factory()->count(3)->create(['stamp_id' => $oldStamp->id, 'activity_id' => $activity->id]);
        StampActivity::factory()->count(2)->create(['stamp_id' => $newStamp->id, 'activity_id' => $activity->id]);

        $result = $this->service->getStampActivities();

        $this->assertSame(2, $result->total());
    }

    public function test_get_stamp_activities_filters_by_section_type_id(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section1 = Section::factory()->create(['section_type_id' => 1]);
        $section2 = Section::factory()->create(['section_type_id' => 2]);
        $activity1 = Activity::factory()->create(['section_id' => $section1->id]);
        $activity2 = Activity::factory()->create(['section_id' => $section2->id]);

        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $activity1->id]);
        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $activity2->id]);

        $result = $this->service->getStampActivities(['section_type_id' => 1]);

        $this->assertSame(1, $result->total());
    }

    public function test_get_stamp_activities_filters_by_status_id(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $statusA = StampActivityStatus::factory()->create();
        $statusB = StampActivityStatus::factory()->create();
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);

        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => $statusA->id,
        ]);
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => $statusB->id,
        ]);

        $result = $this->service->getStampActivities(['status_id' => $statusA->id]);

        $this->assertSame(1, $result->total());
    }

    public function test_get_stamp_activities_filters_by_search_on_activity_name(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $matching = Activity::factory()->create(['section_id' => $section->id, 'name' => 'Actividad especial']);
        $noMatch = Activity::factory()->create(['section_id' => $section->id, 'name' => 'Otra cosa']);

        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $matching->id]);
        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $noMatch->id]);

        $result = $this->service->getStampActivities(['search' => 'especial']);

        $this->assertSame(1, $result->total());
    }

    public function test_get_stamp_activities_paginates_at_10_per_page(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider);

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);
        StampActivity::factory()->count(15)->create(['stamp_id' => $stamp->id, 'activity_id' => $activity->id]);

        $result = $this->service->getStampActivities();

        $this->assertSame(15, $result->total());
        $this->assertCount(10, $result->items());
    }
}
