<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuthMethod;
use App\Models\Section;
use App\Models\SectionType;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Models\StampActivityStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);

        SectionType::insert([
            ['id' => 1, 'name' => 'Módulos', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Protocolos', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function provider(): User
    {
        return User::factory()->create(['auth_method_id' => 1]);
    }

    // -----------------------------------------------------------------------
    // resume (GET /api/dashboard/resume)
    // -----------------------------------------------------------------------

    public function test_resume_requires_authentication(): void
    {
        $this->getJson('/api/dashboard/resume')
            ->assertStatus(401);
    }

    public function test_resume_returns_correct_structure(): void
    {
        $provider = $this->provider();

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonStructure(['days_remaining', 'comments', 'evidences_to_upload', 'protocols_to_upload']);
    }

    public function test_resume_returns_10_days_remaining_when_user_has_no_stamp(): void
    {
        $provider = $this->provider();

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('days_remaining', 10);
    }

    public function test_resume_returns_zero_comments_when_user_has_no_stamp(): void
    {
        $provider = $this->provider();

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('comments', 0);
    }

    public function test_resume_evidences_to_upload_equals_total_type1_activities_when_no_stamp(): void
    {
        $provider = $this->provider();
        $section = Section::factory()->create(['section_type_id' => 1]);
        Activity::factory()->count(3)->create(['section_id' => $section->id]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('evidences_to_upload', 3);
    }

    public function test_resume_protocols_to_upload_equals_total_type2_activities_when_no_stamp(): void
    {
        $provider = $this->provider();
        $section = Section::factory()->create(['section_type_id' => 2]);
        Activity::factory()->count(2)->create(['section_id' => $section->id]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('protocols_to_upload', 2);
    }

    public function test_resume_calculates_days_remaining_from_stamp_creation_date(): void
    {
        $provider = $this->provider();
        Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(3),
        ]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('days_remaining', 7);
    }

    public function test_resume_days_remaining_is_zero_when_stamp_is_older_than_10_days(): void
    {
        $provider = $this->provider();
        Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(15),
        ]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('days_remaining', 0);
    }

    public function test_resume_counts_comments_for_authenticated_user_stamp(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $stampActivity = StampActivity::factory()->create(['stamp_id' => $stamp->id]);
        StampActivityComment::factory()->count(2)->create(['stamp_activity_id' => $stampActivity->id]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('comments', 2);
    }

    public function test_resume_does_not_count_comments_from_other_users_stamps(): void
    {
        $provider = $this->provider();
        $other = $this->provider();

        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $otherStamp = Stamp::factory()->create(['provider_id' => $other->id]);

        $myActivity = StampActivity::factory()->create(['stamp_id' => $stamp->id]);
        $otherActivity = StampActivity::factory()->create(['stamp_id' => $otherStamp->id]);

        StampActivityComment::factory()->count(1)->create(['stamp_activity_id' => $myActivity->id]);
        StampActivityComment::factory()->count(5)->create(['stamp_activity_id' => $otherActivity->id]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('comments', 1);
    }

    public function test_resume_counts_pending_evidences_discounting_uploaded_activities(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);

        $section = Section::factory()->create(['section_type_id' => 1]);
        $activities = Activity::factory()->count(3)->create(['section_id' => $section->id]);

        // 1 subida, 2 pendientes
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activities[0]->id,
            'file_path' => 'uploads/evidencia.pdf',
        ]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('evidences_to_upload', 2);
    }

    public function test_resume_counts_pending_protocols_discounting_uploaded_activities(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);

        $section = Section::factory()->create(['section_type_id' => 2]);
        $activities = Activity::factory()->count(4)->create(['section_id' => $section->id]);

        // 2 subidas, 2 pendientes
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activities[0]->id,
            'file_path' => 'uploads/protocolo1.pdf',
        ]);
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activities[1]->id,
            'file_path' => 'uploads/protocolo2.pdf',
        ]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('protocols_to_upload', 2);
    }

    public function test_resume_uses_only_latest_stamp_of_authenticated_user(): void
    {
        $provider = $this->provider();

        $oldStamp = Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(8),
        ]);
        $newStamp = Stamp::factory()->create([
            'provider_id' => $provider->id,
            'created_at' => now()->subDays(2),
            'stamp_status_id' => $oldStamp->stamp_status_id,
        ]);

        // Comentarios en el stamp antiguo (no deben contarse)
        $oldActivity = StampActivity::factory()->create(['stamp_id' => $oldStamp->id]);
        StampActivityComment::factory()->count(5)->create(['stamp_activity_id' => $oldActivity->id]);

        // Comentario en el stamp más reciente (debe contarse)
        $newActivity = StampActivity::factory()->create(['stamp_id' => $newStamp->id]);
        StampActivityComment::factory()->count(1)->create(['stamp_activity_id' => $newActivity->id]);

        $this->actingAs($provider)->getJson('/api/dashboard/resume')
            ->assertStatus(200)
            ->assertJsonPath('days_remaining', 8)
            ->assertJsonPath('comments', 1);
    }

    // -----------------------------------------------------------------------
    // getStatusWithTotalsActivities (GET /api/dashboard/status-with-totals-activities)
    // -----------------------------------------------------------------------

    public function test_get_status_with_totals_requires_authentication(): void
    {
        $this->getJson('/api/dashboard/status-with-totals-activities')
            ->assertStatus(401);
    }

    public function test_get_status_with_totals_returns_array_with_correct_structure(): void
    {
        $provider = $this->provider();
        StampActivityStatus::factory()->create();

        $this->actingAs($provider)
            ->getJson('/api/dashboard/status-with-totals-activities')
            ->assertStatus(200)
            ->assertJsonStructure([['stamp_id', 'status', 'total_activities']]);
    }

    public function test_get_status_with_totals_counts_only_authenticated_user_activities(): void
    {
        $provider = $this->provider();
        $other = $this->provider();

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

        $this->actingAs($provider)
            ->getJson('/api/dashboard/status-with-totals-activities')
            ->assertStatus(200)
            ->assertJsonFragment(['stamp_id' => $status->id, 'total_activities' => 1]);
    }

    public function test_get_status_with_totals_filters_by_section_type_id(): void
    {
        $provider = $this->provider();
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

        $this->actingAs($provider)
            ->getJson('/api/dashboard/status-with-totals-activities?section_type_id=1')
            ->assertStatus(200)
            ->assertJsonFragment(['stamp_id' => $status->id, 'total_activities' => 1]);
    }

    public function test_get_status_with_totals_filters_by_search(): void
    {
        $provider = $this->provider();
        $status = StampActivityStatus::factory()->create();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);

        $section = Section::factory()->create(['section_type_id' => 1, 'name' => 'Módulo A']);
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

        $this->actingAs($provider)
            ->getJson('/api/dashboard/status-with-totals-activities?search=visible')
            ->assertStatus(200)
            ->assertJsonFragment(['stamp_id' => $status->id, 'total_activities' => 1]);
    }

    // -----------------------------------------------------------------------
    // getStampActivities (GET /api/dashboard/stamp-activities)
    // -----------------------------------------------------------------------

    public function test_get_stamp_activities_requires_authentication(): void
    {
        $this->getJson('/api/dashboard/stamp-activities')
            ->assertStatus(401);
    }

    public function test_get_stamp_activities_returns_empty_array_when_user_has_no_stamp(): void
    {
        $provider = $this->provider();

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities')
            ->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_get_stamp_activities_returns_paginated_results(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);
        StampActivity::factory()->count(3)->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
        ]);

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'total'])
            ->assertJsonCount(3, 'data');
    }

    public function test_get_stamp_activities_returns_correct_data_structure(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);
        StampActivity::factory()->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
        ]);

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'activity_id', 'stamp_activity_status_id', 'created_at', 'activity', 'status']]]);
    }

    public function test_get_stamp_activities_only_returns_activities_of_authenticated_user(): void
    {
        $provider = $this->provider();
        $other = $this->provider();

        $myStamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $otherStamp = Stamp::factory()->create(['provider_id' => $other->id]);

        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);

        StampActivity::factory()->create(['stamp_id' => $myStamp->id, 'activity_id' => $activity->id]);
        StampActivity::factory()->count(5)->create(['stamp_id' => $otherStamp->id, 'activity_id' => $activity->id]);

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_get_stamp_activities_filters_by_section_type_id(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);

        $section1 = Section::factory()->create(['section_type_id' => 1]);
        $section2 = Section::factory()->create(['section_type_id' => 2]);
        $activity1 = Activity::factory()->create(['section_id' => $section1->id]);
        $activity2 = Activity::factory()->create(['section_id' => $section2->id]);

        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $activity1->id]);
        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $activity2->id]);

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities?section_type_id=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_get_stamp_activities_filters_by_status_id(): void
    {
        $provider = $this->provider();
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

        $this->actingAs($provider)
            ->getJson("/api/dashboard/stamp-activities?status_id={$statusA->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_get_stamp_activities_filters_by_search_on_activity_name(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $matching = Activity::factory()->create(['section_id' => $section->id, 'name' => 'Actividad especial']);
        $noMatch = Activity::factory()->create(['section_id' => $section->id, 'name' => 'Otra cosa']);

        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $matching->id]);
        StampActivity::factory()->create(['stamp_id' => $stamp->id, 'activity_id' => $noMatch->id]);

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities?search=especial')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_get_stamp_activities_uses_only_latest_stamp_of_authenticated_user(): void
    {
        $provider = $this->provider();
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

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_stamp_activities_paginates_at_10_per_page(): void
    {
        $provider = $this->provider();
        $stamp = Stamp::factory()->create(['provider_id' => $provider->id]);
        $section = Section::factory()->create(['section_type_id' => 1]);
        $activity = Activity::factory()->create(['section_id' => $section->id]);
        StampActivity::factory()->count(15)->create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
        ]);

        $this->actingAs($provider)
            ->getJson('/api/dashboard/stamp-activities')
            ->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }
}
