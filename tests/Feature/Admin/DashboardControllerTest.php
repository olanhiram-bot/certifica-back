<?php

namespace Tests\Feature\Admin;

use App\Models\AuthMethod;
use App\Models\ModalityType;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Models\StampStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
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
    }

    private function ssoAdmin(): User
    {
        return User::factory()->create(['auth_method_id' => 2]);
    }

    // -----------------------------------------------------------------------
    // index (GET /api/admin/dashboard)
    // -----------------------------------------------------------------------

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard')
            ->assertStatus(401);
    }

    public function test_index_returns_403_when_user_is_not_sso(): void
    {
        $user = User::factory()->create(); // auth_method_id = 1

        $this->actingAs($user)->getJson('/api/admin/dashboard')
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Acceso no autorizado.']);
    }

    public function test_index_returns_paginated_stamps_for_sso_admin(): void
    {
        $admin = $this->ssoAdmin();
        Stamp::factory()->count(15)->create();

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $resp->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(10, 'data');
    }

    public function test_index_returns_only_latest_stamp_per_provider(): void
    {
        $admin = $this->ssoAdmin();
        $provider = User::factory()->create();

        $older = Stamp::factory()->create(['provider_id' => $provider->id]);
        $newer = Stamp::factory()->create(['provider_id' => $provider->id]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $ids = collect($resp->json('data'))->pluck('id')->toArray();

        $this->assertContains($newer->id, $ids);
        $this->assertNotContains($older->id, $ids);
    }

    public function test_index_returns_stamps_ordered_by_take_review_date_asc(): void
    {
        $admin = $this->ssoAdmin();

        $first = Stamp::factory()->create(['take_review_date' => now()->subDays(2)]);
        $second = Stamp::factory()->create(['take_review_date' => now()->subDays(1)]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $ids = collect($resp->json('data'))->pluck('id')->toArray();

        $this->assertEquals([$first->id, $second->id], $ids);
    }

    public function test_index_filters_by_stamp_status_id(): void
    {
        $admin = $this->ssoAdmin();
        $targetStatus = StampStatus::factory()->create();
        $otherStatus = StampStatus::factory()->create();

        $matching = Stamp::factory()->create(['stamp_status_id' => $targetStatus->id]);
        Stamp::factory()->create(['stamp_status_id' => $otherStatus->id]);

        $resp = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard?stamp_status_id='.$targetStatus->id);

        $resp->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_index_filters_by_modality_type_id(): void
    {
        $admin = $this->ssoAdmin();
        $targetModality = ModalityType::factory()->create();
        $otherModality = ModalityType::factory()->create();

        $matching = Stamp::factory()->create(['modality_type_id' => $targetModality->id]);
        Stamp::factory()->create(['modality_type_id' => $otherModality->id]);

        $resp = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard?modality_type_id='.$targetModality->id);

        $resp->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_index_filters_by_search_on_provider_username(): void
    {
        $admin = $this->ssoAdmin();
        $provider = User::factory()->create(['username' => 'proveedor_abc']);
        $other = User::factory()->create(['username' => 'usuario_xyz']);

        $matching = Stamp::factory()->create(['provider_id' => $provider->id]);
        Stamp::factory()->create(['provider_id' => $other->id]);

        $resp = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard?search=proveedor_abc');

        $resp->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_index_returns_empty_data_when_no_stamps_match_filter(): void
    {
        $admin = $this->ssoAdmin();
        Stamp::factory()->count(3)->create();

        $resp = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard?stamp_status_id=99999');

        $resp->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // -----------------------------------------------------------------------
    // export (GET /api/admin/dashboard/export)
    // -----------------------------------------------------------------------

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard/export')
            ->assertStatus(401);
    }

    public function test_export_returns_403_when_user_is_not_sso(): void
    {
        $user = User::factory()->create(); // auth_method_id = 1

        $this->actingAs($user)->getJson('/api/admin/dashboard/export')
            ->assertStatus(403);
    }

    public function test_export_downloads_xlsx_file(): void
    {
        Excel::fake();

        $admin = $this->ssoAdmin();
        Stamp::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/api/admin/dashboard/export')
            ->assertStatus(200);

        $this->freezeTime();
        $expectedFilename = 'proveedores_'.now()->format('Ymd_His').'.xlsx';
        Excel::assertDownloaded($expectedFilename);
    }

    public function test_export_applies_same_filters_as_index(): void
    {
        Excel::fake();

        $admin = $this->ssoAdmin();
        $status = StampStatus::factory()->create();

        Stamp::factory()->count(2)->create(['stamp_status_id' => $status->id]);
        Stamp::factory()->count(3)->create();

        $this->freezeTime();
        $expectedFilename = 'proveedores_'.now()->format('Ymd_His').'.xlsx';

        $this->actingAs($admin)
            ->get('/api/admin/dashboard/export?stamp_status_id='.$status->id)
            ->assertStatus(200);

        Excel::assertDownloaded($expectedFilename, function ($export) use ($status) {
            return $export->query()->where('stamp_status_id', $status->id)->count() === 2;
        });
    }

    // -----------------------------------------------------------------------
    // stampStatusesWithTotals (GET /api/admin/dashboard/stamp-statuses)
    // -----------------------------------------------------------------------

    public function test_stamp_statuses_with_totals_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard/stamp-statuses')
            ->assertStatus(401);
    }

    public function test_stamp_statuses_with_totals_returns_403_when_user_is_not_sso(): void
    {
        $user = User::factory()->create(); // auth_method_id = 1

        $this->actingAs($user)->getJson('/api/admin/dashboard/stamp-statuses')
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Acceso no autorizado.']);
    }

    public function test_stamp_statuses_with_totals_returns_json_array(): void
    {
        $admin = $this->ssoAdmin();
        StampStatus::factory()->count(3)->create();

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/stamp-statuses');

        $resp->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(3);
    }

    public function test_stamp_statuses_with_totals_returns_correct_structure(): void
    {
        $admin = $this->ssoAdmin();
        StampStatus::factory()->create();

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/stamp-statuses');

        $resp->assertStatus(200)
            ->assertJsonStructure([['id', 'name', 'hexadecimal', 'providers_count']]);
    }

    public function test_stamp_statuses_with_totals_returns_correct_provider_counts(): void
    {
        $admin = $this->ssoAdmin();

        $statusA = StampStatus::factory()->create();
        $statusB = StampStatus::factory()->create();
        $statusUnused = StampStatus::factory()->create();

        $providerOne = User::factory()->create();
        $providerTwo = User::factory()->create();

        // providerOne's latest stamp → statusB
        Stamp::factory()->create(['provider_id' => $providerOne->id, 'stamp_status_id' => $statusA->id]);
        Stamp::factory()->create(['provider_id' => $providerOne->id, 'stamp_status_id' => $statusB->id]);

        // providerTwo's only stamp → statusA
        Stamp::factory()->create(['provider_id' => $providerTwo->id, 'stamp_status_id' => $statusA->id]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/stamp-statuses');

        $resp->assertStatus(200);

        $results = collect($resp->json())->keyBy('id');

        $this->assertSame(1, $results[$statusA->id]['providers_count']);
        $this->assertSame(1, $results[$statusB->id]['providers_count']);
        $this->assertSame(0, $results[$statusUnused->id]['providers_count']);
    }

    public function test_stamp_statuses_with_totals_returns_zero_count_when_no_stamps(): void
    {
        $admin = $this->ssoAdmin();
        $status = StampStatus::factory()->create();

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/stamp-statuses');

        $resp->assertStatus(200);

        $result = collect($resp->json())->firstWhere('id', $status->id);

        $this->assertSame(0, $result['providers_count']);
    }

    public function test_stamp_statuses_with_totals_applies_stamp_status_filter(): void
    {
        $admin = $this->ssoAdmin();

        $targetStatus = StampStatus::factory()->create();
        $otherStatus = StampStatus::factory()->create();

        $providerA = User::factory()->create();
        $providerB = User::factory()->create();

        Stamp::factory()->create(['provider_id' => $providerA->id, 'stamp_status_id' => $targetStatus->id]);
        Stamp::factory()->create(['provider_id' => $providerB->id, 'stamp_status_id' => $otherStatus->id]);

        $resp = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard/stamp-statuses?stamp_status_id='.$targetStatus->id);

        $resp->assertStatus(200);

        $results = collect($resp->json())->keyBy('id');

        $this->assertSame(1, $results[$targetStatus->id]['providers_count']);
        $this->assertSame(0, $results[$otherStatus->id]['providers_count']);
    }

    public function test_stamp_statuses_with_totals_applies_modality_type_filter(): void
    {
        $admin = $this->ssoAdmin();

        $status = StampStatus::factory()->create();
        $targetModality = ModalityType::factory()->create();
        $otherModality = ModalityType::factory()->create();

        $providerA = User::factory()->create();
        $providerB = User::factory()->create();

        Stamp::factory()->create([
            'provider_id' => $providerA->id,
            'stamp_status_id' => $status->id,
            'modality_type_id' => $targetModality->id,
        ]);
        Stamp::factory()->create([
            'provider_id' => $providerB->id,
            'stamp_status_id' => $status->id,
            'modality_type_id' => $otherModality->id,
        ]);

        $resp = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard/stamp-statuses?modality_type_id='.$targetModality->id);

        $resp->assertStatus(200);

        $result = collect($resp->json())->firstWhere('id', $status->id);

        $this->assertSame(1, $result['providers_count']);
    }

    public function test_stamp_statuses_with_totals_applies_search_filter(): void
    {
        $admin = $this->ssoAdmin();

        $status = StampStatus::factory()->create();

        $matchingProvider = User::factory()->create(['username' => 'proveedor_buscado']);
        $otherProvider = User::factory()->create(['username' => 'otro_proveedor']);

        Stamp::factory()->create(['provider_id' => $matchingProvider->id, 'stamp_status_id' => $status->id]);
        Stamp::factory()->create(['provider_id' => $otherProvider->id, 'stamp_status_id' => $status->id]);

        $resp = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard/stamp-statuses?search=proveedor_buscado');

        $resp->assertStatus(200);

        $result = collect($resp->json())->firstWhere('id', $status->id);

        $this->assertSame(1, $result['providers_count']);
    }

    public function test_stamp_statuses_with_totals_returns_statuses_ordered_by_id(): void
    {
        $admin = $this->ssoAdmin();

        $first = StampStatus::factory()->create();
        $second = StampStatus::factory()->create();
        $third = StampStatus::factory()->create();

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/stamp-statuses');

        $resp->assertStatus(200);

        $ids = collect($resp->json())->pluck('id')->toArray();

        $this->assertEquals([$first->id, $second->id, $third->id], $ids);
    }

    // -----------------------------------------------------------------------
    // resume (GET /api/admin/dashboard/resume)
    // -----------------------------------------------------------------------

    public function test_resume_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard/resume')
            ->assertStatus(401);
    }

    public function test_resume_returns_403_when_user_is_not_sso(): void
    {
        $user = User::factory()->create(); // auth_method_id = 1

        $this->actingAs($user)->getJson('/api/admin/dashboard/resume')
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Acceso no autorizado.']);
    }

    public function test_resume_returns_correct_structure(): void
    {
        $admin = $this->ssoAdmin();

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/resume');

        $resp->assertStatus(200)
            ->assertJsonStructure(['news', 'comments', 'evidences', 'protocols']);
    }

    public function test_resume_news_counts_stamps_without_reviewer(): void
    {
        $admin = $this->ssoAdmin();

        Stamp::factory()->count(3)->create(['reviewer_id' => null]);
        Stamp::factory()->count(2)->create(['reviewer_id' => $admin->id]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/resume');

        $resp->assertStatus(200)
            ->assertJsonPath('news', 3);
    }

    public function test_resume_comments_counts_comments_on_stamps_reviewed_by_auth_user(): void
    {
        $admin = $this->ssoAdmin();
        $other = User::factory()->create();

        $stampMine = Stamp::factory()->create(['reviewer_id' => $admin->id]);
        $stampOther = Stamp::factory()->create(['reviewer_id' => $other->id]);

        $activityMine = StampActivity::factory()->create(['stamp_id' => $stampMine->id]);
        $activityOther = StampActivity::factory()->create(['stamp_id' => $stampOther->id]);

        StampActivityComment::factory()->count(2)->create(['stamp_activity_id' => $activityMine->id]);
        StampActivityComment::factory()->count(4)->create(['stamp_activity_id' => $activityOther->id]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/resume');

        $resp->assertStatus(200)
            ->assertJsonPath('comments', 2);
    }

    public function test_resume_evidences_counts_activities_on_modality_1_stamps_reviewed_by_auth_user(): void
    {
        $admin = $this->ssoAdmin();

        $modality1 = ModalityType::factory()->create(['id' => 1]);
        $modality2 = ModalityType::factory()->create(['id' => 2]);

        $stampEvidencia = Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'modality_type_id' => $modality1->id,
        ]);
        $stampProtocolo = Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'modality_type_id' => $modality2->id,
        ]);

        StampActivity::factory()->count(3)->create(['stamp_id' => $stampEvidencia->id]);
        StampActivity::factory()->count(2)->create(['stamp_id' => $stampProtocolo->id]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/resume');

        $resp->assertStatus(200)
            ->assertJsonPath('evidences', 3);
    }

    public function test_resume_protocols_counts_activities_on_modality_2_stamps_reviewed_by_auth_user(): void
    {
        $admin = $this->ssoAdmin();

        $modality1 = ModalityType::factory()->create(['id' => 1]);
        $modality2 = ModalityType::factory()->create(['id' => 2]);

        $stampEvidencia = Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'modality_type_id' => $modality1->id,
        ]);
        $stampProtocolo = Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'modality_type_id' => $modality2->id,
        ]);

        StampActivity::factory()->count(3)->create(['stamp_id' => $stampEvidencia->id]);
        StampActivity::factory()->count(4)->create(['stamp_id' => $stampProtocolo->id]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/resume');

        $resp->assertStatus(200)
            ->assertJsonPath('protocols', 4);
    }

    public function test_resume_returns_zeros_when_no_stamps_exist(): void
    {
        $admin = $this->ssoAdmin();

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/resume');

        $resp->assertStatus(200)
            ->assertExactJson([
                'news' => 0,
                'comments' => 0,
                'evidences' => 0,
                'protocols' => 0,
            ]);
    }

    public function test_resume_only_counts_data_for_the_authenticated_reviewer(): void
    {
        $admin = $this->ssoAdmin();
        $other = User::factory()->create(['auth_method_id' => 2]);

        $modality1 = ModalityType::factory()->create(['id' => 1]);

        $stampMine = Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'modality_type_id' => $modality1->id,
        ]);
        $stampOther = Stamp::factory()->create([
            'reviewer_id' => $other->id,
            'modality_type_id' => $modality1->id,
        ]);

        StampActivity::factory()->count(2)->create(['stamp_id' => $stampMine->id]);
        StampActivity::factory()->count(5)->create(['stamp_id' => $stampOther->id]);

        $resp = $this->actingAs($admin)->getJson('/api/admin/dashboard/resume');

        $resp->assertStatus(200)
            ->assertJsonPath('evidences', 2)
            ->assertJsonPath('protocols', 0);
    }

    // -----------------------------------------------------------------------
    // staleAlert (GET /api/admin/dashboard/stale-alert)
    // -----------------------------------------------------------------------

    public function test_stale_alert_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(401);
    }

    public function test_stale_alert_returns_403_when_user_is_not_sso(): void
    {
        $user = User::factory()->create(); // auth_method_id = 1

        $this->actingAs($user)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Acceso no autorizado.']);
    }

    public function test_stale_alert_returns_correct_structure(): void
    {
        $admin = $this->ssoAdmin();

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertJsonStructure(['has_stale']);
    }

    public function test_stale_alert_returns_false_when_no_stamps_exist(): void
    {
        $admin = $this->ssoAdmin();

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertExactJson(['has_stale' => false]);
    }

    public function test_stale_alert_returns_false_when_assigned_stamps_are_recent(): void
    {
        $admin = $this->ssoAdmin();

        Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'updated_at' => now()->subHours(10),
        ]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertExactJson(['has_stale' => false]);
    }

    public function test_stale_alert_returns_false_when_stamp_updated_exactly_2_days_ago(): void
    {
        $admin = $this->ssoAdmin();

        // El umbral es updated_at < now()->subDays(2), por tanto exactamente
        // 2 días atrás no debe disparar la alerta.
        Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'updated_at' => now()->subDays(2),
        ]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertExactJson(['has_stale' => false]);
    }

    public function test_stale_alert_returns_true_when_assigned_stamp_has_no_movement_for_more_than_2_days(): void
    {
        $admin = $this->ssoAdmin();

        Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'updated_at' => now()->subDays(3),
        ]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertExactJson(['has_stale' => true]);
    }

    public function test_stale_alert_only_considers_stamps_assigned_to_auth_user(): void
    {
        $admin = $this->ssoAdmin();
        $other = User::factory()->create(['auth_method_id' => 2]);

        // Stamp del otro validador sin movimiento – no debe afectar al admin
        Stamp::factory()->create([
            'reviewer_id' => $other->id,
            'updated_at' => now()->subDays(5),
        ]);

        // Stamp del admin con movimiento reciente
        Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'updated_at' => now()->subHours(1),
        ]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertExactJson(['has_stale' => false]);
    }

    public function test_stale_alert_returns_false_when_stamp_has_no_reviewer(): void
    {
        $admin = $this->ssoAdmin();

        // Sin reviewer_id, no debe contarse como asignado al admin
        Stamp::factory()->create([
            'reviewer_id' => null,
            'updated_at' => now()->subDays(5),
        ]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertExactJson(['has_stale' => false]);
    }

    public function test_stale_alert_returns_true_when_at_least_one_stale_stamp_among_recent_ones(): void
    {
        $admin = $this->ssoAdmin();

        // Un expediente reciente
        Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'updated_at' => now()->subHours(6),
        ]);

        // Un expediente sin movimientos
        Stamp::factory()->create([
            'reviewer_id' => $admin->id,
            'updated_at' => now()->subDays(4),
        ]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard/stale-alert')
            ->assertStatus(200)
            ->assertExactJson(['has_stale' => true]);
    }
}
