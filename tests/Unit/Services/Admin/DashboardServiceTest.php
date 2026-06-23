<?php

namespace Tests\Unit\Services\Admin;

use App\Models\AuthMethod;
use App\Models\ModalityType;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Models\StampStatus;
use App\Models\User;
use App\Services\Admin\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);

        $this->service = new DashboardService;
    }

    /**
     * Invoca el método privado buildQuery mediante reflexión.
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Stamp>
     */
    private function callBuildQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $method = new \ReflectionMethod(DashboardService::class, 'buildQuery');
        $method->setAccessible(true);

        return $method->invoke($this->service, $request);
    }

    // -----------------------------------------------------------------------
    // buildQuery — latest stamp per provider
    // -----------------------------------------------------------------------

    public function test_build_query_returns_only_latest_stamp_per_provider(): void
    {
        $provider = User::factory()->create();

        $older = Stamp::factory()->create(['provider_id' => $provider->id]);
        $newer = Stamp::factory()->create(['provider_id' => $provider->id]);

        $results = $this->callBuildQuery(new Request)->get();

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($newer->id, $ids);
        $this->assertNotContains($older->id, $ids);
    }

    public function test_build_query_returns_one_stamp_per_provider(): void
    {
        $providerA = User::factory()->create();
        $providerB = User::factory()->create();

        Stamp::factory()->count(3)->create(['provider_id' => $providerA->id]);
        Stamp::factory()->count(2)->create(['provider_id' => $providerB->id]);

        $results = $this->callBuildQuery(new Request)->get();

        $this->assertCount(2, $results);
    }

    public function test_build_query_returns_stamps_ordered_by_take_review_date_asc(): void
    {
        $first = Stamp::factory()->create(['take_review_date' => now()->subDays(2)]);
        $second = Stamp::factory()->create(['take_review_date' => now()->subDays(1)]);

        $results = $this->callBuildQuery(new Request)->get();

        $this->assertEquals([$first->id, $second->id], $results->pluck('id')->toArray());
    }

    // -----------------------------------------------------------------------
    // buildQuery — filters
    // -----------------------------------------------------------------------

    public function test_build_query_filters_by_stamp_status_id(): void
    {
        $targetStatus = StampStatus::factory()->create();
        $otherStatus = StampStatus::factory()->create();

        $matching = Stamp::factory()->create(['stamp_status_id' => $targetStatus->id]);
        Stamp::factory()->create(['stamp_status_id' => $otherStatus->id]);

        $request = new Request(['stamp_status_id' => $targetStatus->id]);
        $results = $this->callBuildQuery($request)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($matching->id, $results->first()->id);
    }

    public function test_build_query_ignores_stamp_status_filter_when_not_filled(): void
    {
        Stamp::factory()->count(3)->create();

        $results = $this->callBuildQuery(new Request)->get();

        $this->assertCount(3, $results);
    }

    public function test_build_query_filters_by_modality_type_id(): void
    {
        $targetModality = ModalityType::factory()->create();
        $otherModality = ModalityType::factory()->create();

        $matching = Stamp::factory()->create(['modality_type_id' => $targetModality->id]);
        Stamp::factory()->create(['modality_type_id' => $otherModality->id]);

        $request = new Request(['modality_type_id' => $targetModality->id]);
        $results = $this->callBuildQuery($request)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($matching->id, $results->first()->id);
    }

    public function test_build_query_filters_by_search_on_provider_username(): void
    {
        $provider = User::factory()->create(['username' => 'proveedor_test']);
        $other = User::factory()->create(['username' => 'otro_usuario']);

        $matching = Stamp::factory()->create(['provider_id' => $provider->id]);
        Stamp::factory()->create(['provider_id' => $other->id]);

        $request = new Request(['search' => 'proveedor_test']);
        $results = $this->callBuildQuery($request)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($matching->id, $results->first()->id);
    }

    public function test_build_query_ignores_empty_search_string(): void
    {
        Stamp::factory()->count(2)->create();

        $request = new Request(['search' => '   ']);
        $results = $this->callBuildQuery($request)->get();

        $this->assertCount(2, $results);
    }

    public function test_build_query_applies_multiple_filters_simultaneously(): void
    {
        $targetStatus = StampStatus::factory()->create();
        $targetModality = ModalityType::factory()->create();
        $otherModality = ModalityType::factory()->create();

        $matching = Stamp::factory()->create([
            'stamp_status_id' => $targetStatus->id,
            'modality_type_id' => $targetModality->id,
        ]);
        // Same status, different modality — should NOT match
        Stamp::factory()->create(['stamp_status_id' => $targetStatus->id, 'modality_type_id' => $otherModality->id]);
        // Different status, same modality — should NOT match
        Stamp::factory()->create(['modality_type_id' => $targetModality->id]);

        $request = new Request([
            'stamp_status_id' => $targetStatus->id,
            'modality_type_id' => $targetModality->id,
        ]);
        $results = $this->callBuildQuery($request)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($matching->id, $results->first()->id);
    }

    // -----------------------------------------------------------------------
    // getPaginatedStamps
    // -----------------------------------------------------------------------

    public function test_get_paginated_stamps_returns_10_items_per_page(): void
    {
        Stamp::factory()->count(15)->create();

        $paginator = $this->service->getPaginatedStamps(new Request);

        $this->assertCount(10, $paginator->items());
        $this->assertEquals(15, $paginator->total());
    }

    public function test_get_paginated_stamps_respects_filters(): void
    {
        $status = StampStatus::factory()->create();

        Stamp::factory()->count(3)->create(['stamp_status_id' => $status->id]);
        Stamp::factory()->count(5)->create();

        $request = new Request(['stamp_status_id' => $status->id]);
        $paginator = $this->service->getPaginatedStamps($request);

        $this->assertEquals(3, $paginator->total());
    }

    public function test_get_paginated_stamps_returns_only_latest_per_provider(): void
    {
        $provider = User::factory()->create();
        Stamp::factory()->count(3)->create(['provider_id' => $provider->id]);

        $paginator = $this->service->getPaginatedStamps(new Request);

        $this->assertEquals(1, $paginator->total());
    }

    // -----------------------------------------------------------------------
    // export
    // -----------------------------------------------------------------------

    public function test_export_returns_binary_file_response(): void
    {
        Excel::fake();

        $response = $this->service->export(new Request);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function test_export_filename_matches_expected_pattern(): void
    {
        Excel::fake();
        $this->freezeTime();

        $expectedFilename = 'proveedores_'.now()->format('Ymd_His').'.xlsx';

        $this->service->export(new Request);

        Excel::assertDownloaded($expectedFilename);
    }

    public function test_export_applies_request_filters_to_query(): void
    {
        Excel::fake();
        $this->freezeTime();

        $status = StampStatus::factory()->create();
        Stamp::factory()->count(2)->create(['stamp_status_id' => $status->id]);
        Stamp::factory()->count(5)->create();

        $expectedFilename = 'proveedores_'.now()->format('Ymd_His').'.xlsx';
        $request = new Request(['stamp_status_id' => $status->id]);

        $this->service->export($request);

        Excel::assertDownloaded($expectedFilename, function ($export) use ($status) {
            return $export->query()->where('stamp_status_id', $status->id)->count() === 2;
        });
    }

    // -----------------------------------------------------------------------
    // getStampStatusesWithCountProviders
    // -----------------------------------------------------------------------

    public function test_get_stamp_statuses_with_count_providers_returns_all_statuses_with_latest_provider_counts(): void
    {
        $pending = StampStatus::factory()->create(['name' => 'Pendiente']);
        $approved = StampStatus::factory()->create(['name' => 'Aprobado']);
        $unused = StampStatus::factory()->create(['name' => 'Sin uso']);

        $providerOne = User::factory()->create();
        $providerTwo = User::factory()->create();

        Stamp::factory()->create([
            'provider_id' => $providerOne->id,
            'stamp_status_id' => $pending->id,
        ]);
        Stamp::factory()->create([
            'provider_id' => $providerOne->id,
            'stamp_status_id' => $approved->id,
        ]);
        Stamp::factory()->create([
            'provider_id' => $providerTwo->id,
            'stamp_status_id' => $pending->id,
        ]);

        $results = collect($this->service->getStampStatusesWithCountProviders(new Request))
            ->keyBy('id');

        $this->assertCount(3, $results);
        $this->assertSame(1, $results[$pending->id]['providers_count']);
        $this->assertSame(1, $results[$approved->id]['providers_count']);
        $this->assertSame(0, $results[$unused->id]['providers_count']);
    }

    public function test_get_stamp_statuses_with_count_providers_applies_same_filters_as_index(): void
    {
        $targetStatus = StampStatus::factory()->create(['name' => 'Objetivo']);
        $otherStatus = StampStatus::factory()->create(['name' => 'Otro']);
        $unusedStatus = StampStatus::factory()->create(['name' => 'Sin coincidencias']);
        $targetModality = ModalityType::factory()->create();
        $otherModality = ModalityType::factory()->create();

        $matchingProvider = User::factory()->create(['username' => 'proveedor_objetivo']);
        $otherProvider = User::factory()->create(['username' => 'otro_proveedor']);
        $wrongModalityProvider = User::factory()->create(['username' => 'proveedor_otro']);

        Stamp::factory()->create([
            'provider_id' => $matchingProvider->id,
            'stamp_status_id' => $targetStatus->id,
            'modality_type_id' => $targetModality->id,
        ]);
        Stamp::factory()->create([
            'provider_id' => $otherProvider->id,
            'stamp_status_id' => $otherStatus->id,
            'modality_type_id' => $targetModality->id,
        ]);
        Stamp::factory()->create([
            'provider_id' => $wrongModalityProvider->id,
            'stamp_status_id' => $targetStatus->id,
            'modality_type_id' => $otherModality->id,
        ]);

        $request = new Request([
            'stamp_status_id' => $targetStatus->id,
            'modality_type_id' => $targetModality->id,
            'search' => 'proveedor_objetivo',
        ]);

        $results = collect($this->service->getStampStatusesWithCountProviders($request))
            ->keyBy('id');

        $this->assertSame(1, $results[$targetStatus->id]['providers_count']);
        $this->assertSame(0, $results[$otherStatus->id]['providers_count']);
        $this->assertSame(0, $results[$unusedStatus->id]['providers_count']);
    }

    // -----------------------------------------------------------------------
    // getResume
    // -----------------------------------------------------------------------

    public function test_get_resume_returns_correct_keys(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $result = $this->service->getResume(new Request);

        $this->assertArrayHasKey('news', $result);
        $this->assertArrayHasKey('comments', $result);
        $this->assertArrayHasKey('evidences', $result);
        $this->assertArrayHasKey('protocols', $result);
    }

    public function test_get_resume_returns_zeros_when_no_stamps_exist(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $result = $this->service->getResume(new Request);

        $this->assertSame(0, $result['news']);
        $this->assertSame(0, $result['comments']);
        $this->assertSame(0, $result['evidences']);
        $this->assertSame(0, $result['protocols']);
    }

    public function test_get_resume_news_counts_stamps_without_reviewer(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Stamp::factory()->count(3)->create(['reviewer_id' => null]);
        Stamp::factory()->count(2)->create(['reviewer_id' => $admin->id]);

        $result = $this->service->getResume(new Request);

        $this->assertSame(3, $result['news']);
    }

    public function test_get_resume_news_counts_all_stamps_without_reviewer_not_just_latest(): void
    {
        $admin = User::factory()->create();
        $provider = User::factory()->create();
        $this->actingAs($admin);

        // Multiple stamps for the same provider with no reviewer — all must be counted
        Stamp::factory()->count(3)->create(['provider_id' => $provider->id, 'reviewer_id' => null]);

        $result = $this->service->getResume(new Request);

        $this->assertSame(3, $result['news']);
    }

    public function test_get_resume_comments_counts_comments_on_stamps_reviewed_by_auth_user(): void
    {
        $admin = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($admin);

        $stampMine = Stamp::factory()->create(['reviewer_id' => $admin->id]);
        $stampOther = Stamp::factory()->create(['reviewer_id' => $other->id]);

        $activityMine = StampActivity::factory()->create(['stamp_id' => $stampMine->id]);
        $activityOther = StampActivity::factory()->create(['stamp_id' => $stampOther->id]);

        StampActivityComment::factory()->count(2)->create(['stamp_activity_id' => $activityMine->id]);
        StampActivityComment::factory()->count(5)->create(['stamp_activity_id' => $activityOther->id]);

        $result = $this->service->getResume(new Request);

        $this->assertSame(2, $result['comments']);
    }

    public function test_get_resume_evidences_counts_activities_on_modality_1_stamps_reviewed_by_auth_user(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

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

        $result = $this->service->getResume(new Request);

        $this->assertSame(3, $result['evidences']);
    }

    public function test_get_resume_protocols_counts_activities_on_modality_2_stamps_reviewed_by_auth_user(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

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

        $result = $this->service->getResume(new Request);

        $this->assertSame(4, $result['protocols']);
    }

    public function test_get_resume_only_counts_activities_reviewed_by_auth_user(): void
    {
        $admin = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($admin);

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

        $result = $this->service->getResume(new Request);

        $this->assertSame(2, $result['evidences']);
        $this->assertSame(0, $result['protocols']);
    }
}
