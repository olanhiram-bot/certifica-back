<?php

namespace Tests\Feature;

use App\Models\AuthMethod;
use App\Models\CourseCall;
use App\Models\ModalityType;
use App\Models\Stamp;
use App\Models\StampStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);
        StampStatus::insert([
            ['id' => 1, 'name' => 'Nuevo', 'hexadecimal' => '#9164CC'],
            ['id' => 2, 'name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['id' => 3, 'name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['id' => 4, 'name' => 'Aprobado', 'hexadecimal' => '#00BC70'],
            ['id' => 5, 'name' => 'Vencido', 'hexadecimal' => '#FF4438'],
        ]);
        CourseCall::insert([
            ['id' => 1, 'name' => 'Convocatoria 1', 'vigency_start_date' => now()->subDays(5), 'vigency_end_date' => now()->addDays(5), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    // -----------------------------------------------------------------------
    // index
    // -----------------------------------------------------------------------

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/stamps')
            ->assertStatus(401);
    }

    public function test_index_returns_all_stamps_without_pagination(): void
    {
        $user = User::factory()->create();
        Stamp::factory()->count(3)->create();

        $resp = $this->actingAs($user)->getJson('/api/stamps');

        $resp->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_index_returns_paginated_stamps_when_page_param_is_present(): void
    {
        $user = User::factory()->create();
        Stamp::factory()->count(15)->create();

        $resp = $this->actingAs($user)->getJson('/api/stamps?page=1');

        $resp->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(10, 'data');
    }

    public function test_index_returns_stamps_ordered_by_id_desc(): void
    {
        $user = User::factory()->create();
        $first = Stamp::factory()->create();
        $last = Stamp::factory()->create();

        $resp = $this->actingAs($user)->getJson('/api/stamps');

        $ids = collect($resp->json('data'))->pluck('id')->toArray();
        $this->assertEquals([$last->id, $first->id], $ids);
    }

    // -----------------------------------------------------------------------
    // lastByProvider (GET /api/stamps/current-by-provider)
    // -----------------------------------------------------------------------

    public function test_last_by_provider_requires_authentication(): void
    {
        $this->getJson('/api/stamps/current-by-provider')
            ->assertStatus(401);
    }

    public function test_last_by_provider_returns_most_recent_stamp_of_authenticated_provider(): void
    {
        $provider = User::factory()->create();

        $this->travel(-5)->minutes();
        $older = Stamp::factory()->create(['provider_id' => $provider->id]);
        $this->travelBack();

        $newer = Stamp::factory()->create(['provider_id' => $provider->id]);

        $resp = $this->actingAs($provider)->getJson('/api/stamps/current-by-provider');

        $resp->assertStatus(200)
            ->assertJsonPath('data.id', $newer->id);
    }

    public function test_last_by_provider_does_not_return_stamps_from_other_providers(): void
    {
        $provider = User::factory()->create();
        $other = User::factory()->create();
        $otherStamp = Stamp::factory()->create(['provider_id' => $other->id]);

        // Provider has their own stamp
        $ownStamp = Stamp::factory()->create(['provider_id' => $provider->id]);

        $resp = $this->actingAs($provider)->getJson('/api/stamps/current-by-provider');

        $resp->assertStatus(200)
            ->assertJsonPath('data.id', $ownStamp->id);
        $this->assertNotEquals($otherStamp->id, $resp->json('data.id'));
    }

    // -----------------------------------------------------------------------
    // store (POST /api/stamps)
    // -----------------------------------------------------------------------

    public function test_store_requires_authentication(): void
    {
        $modality = ModalityType::factory()->create();

        $this->postJson('/api/stamps', ['modality_type_id' => $modality->id])
            ->assertStatus(401);
    }

    public function test_store_creates_stamp_and_assigns_authenticated_user_as_provider(): void
    {
        $provider = User::factory()->create();
        $modality = ModalityType::factory()->create();

        $resp = $this->actingAs($provider)->postJson('/api/stamps', [
            'modality_type_id' => $modality->id,
        ]);

        $resp->assertStatus(201)
            ->assertJsonPath('data.provider_id', $provider->id)
            ->assertJsonPath('data.modality_type_id', $modality->id);

        $this->assertDatabaseHas('stamps', [
            'provider_id' => $provider->id,
            'modality_type_id' => $modality->id,
        ]);
    }

    public function test_store_fails_validation_when_modality_type_id_is_missing(): void
    {
        $provider = User::factory()->create();

        $resp = $this->actingAs($provider)->postJson('/api/stamps', []);

        $resp->assertStatus(422)
            ->assertJsonValidationErrors(['modality_type_id']);
    }

    public function test_store_fails_validation_when_modality_type_does_not_exist(): void
    {
        $provider = User::factory()->create();

        $resp = $this->actingAs($provider)->postJson('/api/stamps', [
            'modality_type_id' => 9999,
        ]);

        $resp->assertStatus(422)
            ->assertJsonValidationErrors(['modality_type_id']);
    }

    // -----------------------------------------------------------------------
    // show (GET /api/stamps/{stamp})
    // -----------------------------------------------------------------------

    public function test_show_requires_authentication(): void
    {
        $stamp = Stamp::factory()->create();

        $this->getJson("/api/stamps/{$stamp->id}")
            ->assertStatus(401);
    }

    public function test_show_returns_correct_stamp(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create(
            [
                'provider_id' => $user->id,
            ],
        );

        $resp = $this->actingAs($user)->getJson("/api/stamps/{$stamp->id}");

        $resp->assertStatus(200)
            ->assertJsonPath('data.id', $stamp->id)
            ->assertJsonPath('data.modality_type_id', $stamp->modality_type_id);
    }

    public function test_show_returns_404_for_non_existing_stamp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/stamps/9999')
            ->assertStatus(404);
    }

    public function test_show_allows_provider_owner_even_when_reviewer_assigned(): void
    {
        $provider = User::factory()->create(['auth_method_id' => 1]);
        $reviewer = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create([
            'provider_id' => $provider->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $this->actingAs($provider)->getJson("/api/stamps/{$stamp->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $stamp->id);
    }

    public function test_show_allows_admin_when_stamp_not_yet_taken(): void
    {
        $admin = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create(['reviewer_id' => null]);

        $this->actingAs($admin)->getJson("/api/stamps/{$stamp->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $stamp->id);
    }

    public function test_show_allows_assigned_reviewer(): void
    {
        $reviewer = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create(['reviewer_id' => $reviewer->id]);

        $this->actingAs($reviewer)->getJson("/api/stamps/{$stamp->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $stamp->id);
    }

    public function test_show_blocks_other_admin_when_stamp_taken_by_another_reviewer(): void
    {
        $reviewer = User::factory()->create(['auth_method_id' => 2]);
        $otherAdmin = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create(['reviewer_id' => $reviewer->id]);

        $this->actingAs($otherAdmin)->getJson("/api/stamps/{$stamp->id}")
            ->assertStatus(403)
            ->assertJsonPath('message', 'El expediente ya fue tomado por otro revisor');
    }

    public function test_destroy_stamp()
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();

        $resp = $this->actingAs($user)->deleteJson("/api/stamps/{$stamp->id}", ['deleted_reason' => 'Prueba eliminación']);
        $resp->assertStatus(204);
        $this->assertSoftDeleted('stamps', ['id' => $stamp->id]);
    }

    // -----------------------------------------------------------------------
    // takeReviewerStamp (PUT /api/admin/stamps/{stamp}/take-reviewer)
    // -----------------------------------------------------------------------

    public function test_take_reviewer_requires_authentication(): void
    {
        $stamp = Stamp::factory()->create();

        $this->putJson('/api/admin/stamps/'.$stamp->id.'/take-reviewer')
            ->assertStatus(401);
    }

    public function test_take_reviewer_returns_403_when_user_is_not_sso(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();

        $this->actingAs($user)->putJson('/api/admin/stamps/'.$stamp->id.'/take-reviewer')
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Acceso no autorizado.']);
    }

    public function test_take_reviewer_assigns_reviewer_successfully(): void
    {
        $admin = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create(['reviewer_id' => null]);

        $resp = $this->actingAs($admin)->putJson('/api/admin/stamps/'.$stamp->id.'/take-reviewer');

        $resp->assertStatus(200)
            ->assertJsonPath('data.reviewer_id', $admin->id);

        $this->assertDatabaseHas('stamps', ['id' => $stamp->id, 'reviewer_id' => $admin->id]);
    }

    public function test_take_reviewer_fails_when_already_assigned(): void
    {
        $admin = User::factory()->create(['auth_method_id' => 2]);
        $other = User::factory()->create();
        $stamp = Stamp::factory()->create(['reviewer_id' => $other->id]);

        $this->actingAs($admin)->putJson('/api/admin/stamps/'.$stamp->id.'/take-reviewer')
            ->assertStatus(400)
            ->assertJsonPath('message', 'El expediente ya fue asignado');
    }
}
