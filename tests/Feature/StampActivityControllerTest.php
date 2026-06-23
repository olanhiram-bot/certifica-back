<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuthMethod;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityStatus;
use App\Models\StampStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StampActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        require_once base_path('app/Helpers.php');
        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);
        StampActivityStatus::insert([
            ['id' => 1, 'name' => 'Sin Iniciar', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Pendiente', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Con Evidencia', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'En Revision', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Con Observaciones', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Aprobado', 'created_at' => now(), 'updated_at' => now()],
        ]);
        StampStatus::insert([
            ['id' => 1, 'name' => 'Nuevo', 'hexadecimal' => '#9164CC'],
            ['id' => 2, 'name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['id' => 3, 'name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['id' => 4, 'name' => 'Aprobado', 'hexadecimal' => '#00BC70'],
            ['id' => 5, 'name' => 'Vencido', 'hexadecimal' => '#FF4438'],
        ]);

    }

    // -----------------------------------------------------------------------
    // index  GET /api/stamps/{stamp}/stamp-activities
    // -----------------------------------------------------------------------

    public function test_index_requires_authentication(): void
    {
        $stamp = Stamp::factory()->create();

        $this->getJson("/api/stamps/{$stamp->id}/stamp-activities")
            ->assertStatus(401);
    }

    public function test_index_returns_all_stamp_activities_for_given_stamp(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activities = Activity::factory()->count(3)->create();

        foreach ($activities as $activity) {
            StampActivity::create([
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
                'stamp_activity_status_id' => 1,
            ]);
        }

        $resp = $this->actingAs($user)->getJson("/api/stamps/{$stamp->id}/stamp-activities");

        $resp->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_index_returns_paginated_stamp_activities_when_page_param_is_present(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activities = Activity::factory()->count(15)->create();

        foreach ($activities as $activity) {
            StampActivity::create([
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
                'stamp_activity_status_id' => 1,
            ]);
        }

        $resp = $this->actingAs($user)->getJson("/api/stamps/{$stamp->id}/stamp-activities?page=1");

        $resp->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(10, 'data');
    }

    public function test_index_only_returns_stamp_activities_for_given_stamp(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $otherStamp = Stamp::factory()->create();

        $ownActivity = Activity::factory()->create();
        $otherActivity = Activity::factory()->create();

        StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $ownActivity->id,
            'stamp_activity_status_id' => 1,
        ]);

        StampActivity::create([
            'stamp_id' => $otherStamp->id,
            'activity_id' => $otherActivity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $resp = $this->actingAs($user)->getJson("/api/stamps/{$stamp->id}/stamp-activities");

        $resp->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_index_returns_empty_collection_when_stamp_has_no_activities(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();

        $resp = $this->actingAs($user)->getJson("/api/stamps/{$stamp->id}/stamp-activities");

        $resp->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_index_returns_404_for_nonexistent_stamp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/stamps/9999/stamp-activities')
            ->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // store  POST /api/stamps/{stamp}/stamp-activities
    // -----------------------------------------------------------------------

    public function test_store_requires_authentication(): void
    {
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();

        $this->postJson("/api/stamps/{$stamp->id}/stamp-activities", [
            'activity_id' => $activity->id,
        ])->assertStatus(401);
    }

    public function test_store_creates_stamp_activity_with_default_status(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();

        $resp = $this->actingAs($user)->postJson("/api/stamps/{$stamp->id}/stamp-activities", [
            'activity_id' => $activity->id,
        ]);

        $resp->assertStatus(201)
            ->assertJsonPath('data.stamp_id', $stamp->id)
            ->assertJsonPath('data.activity_id', $activity->id)
            ->assertJsonPath('data.stamp_activity_status_id', 1);

        $this->assertDatabaseHas('stamp_activities', [
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);
    }

    public function test_store_requires_activity_id(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();

        $this->actingAs($user)->postJson("/api/stamps/{$stamp->id}/stamp-activities", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['activity_id']);
    }

    public function test_store_returns_422_for_nonexistent_activity_id(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();

        $this->actingAs($user)->postJson("/api/stamps/{$stamp->id}/stamp-activities", [
            'activity_id' => 9999,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['activity_id']);
    }

    public function test_store_is_idempotent_for_same_stamp_and_activity(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();

        StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $this->actingAs($user)->postJson("/api/stamps/{$stamp->id}/stamp-activities", [
            'activity_id' => $activity->id,
        ])->assertSuccessful();

        $this->assertDatabaseCount('stamp_activities', 1);
    }

    public function test_store_returns_404_for_nonexistent_stamp(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($user)->postJson('/api/stamps/9999/stamp-activities', [
            'activity_id' => $activity->id,
        ])->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // show  GET /api/stamp-activities/{stampActivity}
    // -----------------------------------------------------------------------

    public function test_show_requires_authentication(): void
    {
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $this->getJson("/api/stamp-activities/{$stampActivity->id}")
            ->assertStatus(401);
    }

    public function test_show_returns_stamp_activity(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $resp = $this->actingAs($user)->getJson("/api/stamp-activities/{$stampActivity->id}");

        $resp->assertStatus(200)
            ->assertJsonPath('data.id', $stampActivity->id)
            ->assertJsonPath('data.stamp_id', $stamp->id)
            ->assertJsonPath('data.activity_id', $activity->id)
            ->assertJsonPath('data.stamp_activity_status_id', 1);
    }

    public function test_show_returns_correct_json_structure(): void
    {
        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $resp = $this->actingAs($user)->getJson("/api/stamp-activities/{$stampActivity->id}");

        $resp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'stamp_id',
                    'activity_id',
                    'stamp_activity_status_id',
                    'score',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_stamp_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/stamp-activities/9999')
            ->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // addFile  POST /api/stamp-activities/{stampActivity}/add-file
    // -----------------------------------------------------------------------

    public function test_add_file_requires_authentication(): void
    {
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        Storage::fake('local');

        $this->postJson("/api/stamp-activities/{$stampActivity->id}/add-file", [
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertStatus(401);
    }

    public function test_add_file_stores_file_and_updates_stamp_activity(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

        $resp = $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/add-file", [
            'file' => $file,
        ]);

        $resp->assertStatus(200)
            ->assertJsonPath('data.file_name', 'report.pdf')
            ->assertJsonPath('data.extension', 'pdf');

        $this->assertDatabaseHas('stamp_activities', [
            'id' => $stampActivity->id,
            'file_name' => 'report.pdf',
            'extension' => 'pdf',
        ]);
    }

    public function test_add_file_returns_correct_json_structure(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $resp = $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/add-file", [
            'file' => UploadedFile::fake()->create('documento.pdf', 500),
        ]);

        $resp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'stamp_id',
                    'activity_id',
                    'stamp_activity_status_id',
                    'file_url',
                    'file_path',
                    'file_name',
                    'file_size',
                    'extension',
                    'unit_size',
                    'upload_date',
                ],
            ]);
    }

    public function test_add_file_replaces_existing_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();

        $oldFile = UploadedFile::fake()->create('old.pdf', 100, 'application/pdf');
        $oldPath = $oldFile->store('provider/old', 'local');

        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
            'file_path' => $oldPath,
            'file_name' => 'old.pdf',
        ]);

        Storage::disk('local')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->create('new.pdf', 200, 'application/pdf');

        $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/add-file", [
            'file' => $newFile,
        ])->assertStatus(200)
            ->assertJsonPath('data.file_name', 'new.pdf');

        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_add_file_rejects_disallowed_mime_types(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/add-file", [
            'file' => UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream'),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_add_file_rejects_files_exceeding_size_limit(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        // 10241 KB exceeds the 10240 KB (10 MB) limit
        $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/add-file", [
            'file' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_add_file_returns_404_for_nonexistent_stamp_activity(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/stamp-activities/9999/add-file', [
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertStatus(404);
    }

    public function test_add_file_stores_file_in_correct_path(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/add-file", [
            'file' => UploadedFile::fake()->create('documento.pdf', 500),
        ])->assertStatus(200);

        $updatedPath = StampActivity::find($stampActivity->id)->file_path;
        Storage::disk('local')->assertExists($updatedPath);

        $this->assertStringContainsString(
            "provider/{$user->username}/stamp/{$stamp->id}/activity/{$activity->id}",
            $updatedPath,
        );
    }

    public function test_approved_stamp_activity_whith_error_status()
    {
        $admin = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create();
        $stamp->update(['reviewer_id' => $admin->id]);
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $resp = $this->actingAs($admin)->putJson('/api/stamp-activities/'.$stampActivity->id.'/approved');
        $resp->assertStatus(422)->assertJsonPath('message', 'Estatus inválido para aprobación');
    }

    public function test_approved_stamp_activity_whith_error_permission()
    {
        $admin = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create();
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);

        $resp = $this->actingAs($admin)->putJson('/api/stamp-activities/'.$stampActivity->id.'/approved');
        $resp->assertStatus(403)->assertJsonPath('message', 'No tienes permiso para aprobar esta actividad');
    }

    public function test_approved_stamp_activity()
    {
        $admin = User::factory()->create(['auth_method_id' => 2]);
        $stamp = Stamp::factory()->create();
        $stamp->update(['reviewer_id' => $admin->id]);
        $activity = Activity::factory()->create();
        $stampActivity = StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 4,
        ]);

        $resp = $this->actingAs($admin)->putJson('/api/stamp-activities/'.$stampActivity->id.'/approved');
        $resp->assertStatus(200);
    }
}
