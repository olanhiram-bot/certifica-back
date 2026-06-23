<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuthMethod;
use App\Models\Section;
use App\Models\SectionType;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityStatus;
use App\Models\StampStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AuthMethod::insert([
            ['id' => 1, 'name' => 'django'],
            ['id' => 2, 'name' => 'sso'],
        ]);
        StampActivityStatus::insert([
            ['id' => 1, 'name' => 'Sin Iniciar',       'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Pendiente',          'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Con Evidencia',      'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'En Revision',        'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Con Observaciones',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Aprobado',           'created_at' => now(), 'updated_at' => now()],
        ]);
        StampStatus::insert([
            ['id' => 1, 'name' => 'Nuevo',       'hexadecimal' => '#9164CC'],
            ['id' => 2, 'name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['id' => 3, 'name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['id' => 4, 'name' => 'Aprobado',    'hexadecimal' => '#00BC70'],
            ['id' => 5, 'name' => 'Vencido',     'hexadecimal' => '#FF4438'],
        ]);
    }

    public function test_index_returns_activities_for_given_section()
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();
        Activity::factory()->count(3)->create(['section_id' => $section->id]);

        $resp = $this->actingAs($user)->getJson("/api/sections/{$section->id}/activities");
        $resp->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_store_saves_activity_with_files()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['auth_method_id' => 2]);
        $section = Section::factory()->create();

        $data = [
            'name' => 'Actividad prueba',
            'description' => 'Descripcion',
            'logo_file' => UploadedFile::fake()->image('logo.jpg'),
            'content_file' => UploadedFile::fake()->create('document.pdf', 100),
        ];

        $resp = $this->actingAs($admin)->postJson("/api/admin/sections/{$section->id}/activities", $data);

        $resp->assertStatus(201)->assertJsonFragment(['name' => 'Actividad prueba']);

        $activityId = $resp->json('data.id');
        $this->assertDatabaseHas('activities', ['id' => $activityId, 'section_id' => $section->id]);

        $filePath = $resp->json('data.file_url');
        $contentPath = $resp->json('data.file_url_content');

        $this->assertTrue(Storage::disk('public')->exists($filePath));
        $this->assertTrue(Storage::disk('public')->exists($contentPath));
    }

    public function test_update_replaces_files_and_deletes_old()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['auth_method_id' => 2]);
        $section = Section::factory()->create();

        // Create archivos iniciales
        $initialLogo = UploadedFile::fake()->image('old.jpg');
        $initialLogoPath = $initialLogo->store('activities', 'public');

        $initialContent = UploadedFile::fake()->create('old.pdf', 50);
        $initialContentPath = $initialContent->store('activities', 'public');

        $activity = Activity::factory()->create([
            'section_id' => $section->id,
            'file_url' => $initialLogoPath,
            'file_url_content' => $initialContentPath,
        ]);

        $newLogo = UploadedFile::fake()->image('new.jpg');
        $newContent = UploadedFile::fake()->create('new.pdf', 80);

        $updateData = [
            'name' => 'Actividad actualizada',
            'description' => 'Desc actualizada',
            'logo_file' => $newLogo,
            'content_file' => $newContent,
            'section_id' => $section->id,
        ];

        $resp = $this->actingAs($admin)->putJson("/api/admin/activities/{$activity->id}", $updateData);

        $resp->assertStatus(200)->assertJsonFragment(['name' => 'Actividad actualizada']);

        $activity->refresh();

        // dd($activity->name, $initialLogoPath, $activity->file_url, $initialContentPath, $activity->file_url_content);
        $this->assertNotEquals($initialLogoPath, $activity->file_url);
        $this->assertTrue(Storage::disk('public')->exists($activity->file_url));
        $this->assertFalse(Storage::disk('public')->exists($initialLogoPath));

        $this->assertNotEquals($initialContentPath, $activity->file_url_content);
        $this->assertTrue(Storage::disk('public')->exists($activity->file_url_content));
        $this->assertFalse(Storage::disk('public')->exists($initialContentPath));
    }

    public function test_destroy_deletes_files_and_soft_deletes()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['auth_method_id' => 2]);
        $section = Section::factory()->create();

        $initialLogo = UploadedFile::fake()->image('old.jpg');
        $initialLogoPath = $initialLogo->store('activities', 'public');

        $initialContent = UploadedFile::fake()->create('old.pdf', 50);
        $initialContentPath = $initialContent->store('activities', 'public');

        $activity = Activity::factory()->create([
            'section_id' => $section->id,
            'file_url' => $initialLogoPath,
            'file_url_content' => $initialContentPath,
        ]);

        $resp = $this->actingAs($admin)->deleteJson("/api/admin/activities/{$activity->id}", ['deleted_reason' => 'Prueba eliminación']);

        $resp->assertStatus(204);

        $this->assertFalse(Storage::disk('public')->exists($initialLogoPath));
        $this->assertFalse(Storage::disk('public')->exists($initialContentPath));

        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
    }

    public function test_destroy_removes_stamp_activities_and_recalculates_percentages(): void
    {
        $admin = User::factory()->create(['auth_method_id' => 2]);

        $sectionType = SectionType::factory()->create(['id' => 1]);
        $section = Section::factory()->create(['section_type_id' => $sectionType->id]);

        // 2 actividades en la sección
        $activityToDelete = Activity::factory()->for($section)->create();
        $otherActivity = Activity::factory()->for($section)->create();

        $stamp = Stamp::factory()->create([
            'percent_moduls' => 100,
            'percent_evaluations' => 0,
            'percent' => 50,
        ]);

        // stamp tiene ambas actividades completadas
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $activityToDelete->id, 'stamp_activity_status_id' => 6]);
        StampActivity::create(['stamp_id' => $stamp->id, 'activity_id' => $otherActivity->id,    'stamp_activity_status_id' => 6]);

        $resp = $this->actingAs($admin)->deleteJson(
            "/api/admin/activities/{$activityToDelete->id}",
            ['deleted_reason' => 'Prueba'],
        );

        $resp->assertStatus(204);

        // El stamp_activity de la actividad eliminada debe estar soft-deleted
        $this->assertSoftDeleted('stamp_activities', [
            'stamp_id' => $stamp->id,
            'activity_id' => $activityToDelete->id,
        ]);

        // El stamp_activity de la otra actividad sigue intacto
        $this->assertDatabaseHas('stamp_activities', [
            'activity_id' => $otherActivity->id,
            'deleted_at' => null,
        ]);

        // Porcentaje recalculado: 1 completada / 1 actividad restante = 100 %
        $stamp->refresh();
        $this->assertEquals(100, $stamp->percent_moduls);
    }

    public function test_content_is_accessible_without_authentication(): void
    {
        Storage::fake('public');

        $contentFile = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $contentPath = $contentFile->store('activities', 'public');

        $activity = Activity::factory()->create([
            'file_url_content' => $contentPath,
        ]);

        $this->getJson("/api/activities/{$activity->id}/content")
            ->assertStatus(200);
    }

    public function test_content_returns_pdf_stream_when_file_exists(): void
    {
        Storage::fake('public');

        $contentFile = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $contentPath = $contentFile->store('activities', 'public');

        $activity = Activity::factory()->create([
            'file_url_content' => $contentPath,
        ]);

        $resp = $this->get("/api/activities/{$activity->id}/content");

        $resp->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_content_returns_404_when_file_url_content_is_null(): void
    {
        $activity = Activity::factory()->create(['file_url_content' => null]);

        $resp = $this->getJson("/api/activities/{$activity->id}/content");

        $resp->assertStatus(404)
            ->assertJsonFragment(['message' => 'Archivo no encontrado']);
    }

    public function test_content_returns_404_when_file_does_not_exist_in_storage(): void
    {
        Storage::fake('public');

        $activity = Activity::factory()->create([
            'file_url_content' => 'activities/nonexistent.pdf',
        ]);

        $resp = $this->getJson("/api/activities/{$activity->id}/content");

        $resp->assertStatus(404)
            ->assertJsonFragment(['message' => 'Archivo no encontrado']);
    }
}
