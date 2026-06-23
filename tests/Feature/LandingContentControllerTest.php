<?php

namespace Tests\Feature;

use App\Models\AuthMethod;
use App\Models\LandingContent;
use App\Models\LandingContentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingContentControllerTest extends TestCase
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

    public function test_index_returns_items()
    {
        $type = LandingContentType::factory()->create();
        LandingContent::factory()->count(2)->create(['landing_content_type_id' => $type->id]);
        $resp = $this->getJson('/api/content-types/'.$type->id.'/landing-contents');
        $resp->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_store_with_file_stores_and_returns_path()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['auth_method_id' => 2]);

        $type = LandingContentType::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $resp = $this->actingAs($admin)->postJson('/api/admin/content-types/'.$type->id.'/landing-contents', [
            'name' => 'Prueba',
            'description' => 'Desc',
            'file_url' => $file,
            'position' => 1,
            'landing_content_type_id' => $type->id,
        ]);
        $resp->assertStatus(201);
        $data = $resp->json();
        $data = $data['data'];
        $this->assertArrayHasKey('file_url', $data);
        $this->assertNotNull($data['file_url']);
        $this->assertTrue(Storage::disk('public')->exists($data['file_url']));
        $this->assertDatabaseHas('landing_contents', ['id' => $data['id'], 'file_url' => $data['file_url']]);
    }

    public function test_show_returns_item()
    {
        $type = LandingContentType::factory()->create();
        $item = LandingContent::factory()->create(['landing_content_type_id' => $type->id]);

        $resp = $this->getJson('/api/landing-contents/'.$item->id);
        $resp->assertStatus(200);
        $resp->assertJsonFragment(['id' => $item->id]);
    }

    public function test_update_replaces_file()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['auth_method_id' => 2]);
        $type = LandingContentType::factory()->create();

        // create existing file
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $oldPath = $oldFile->store('landing', 'public');

        $item = LandingContent::factory()->create([
            'file_url' => $oldPath,
            'landing_content_type_id' => $type->id,
        ]);

        $this->assertTrue(Storage::disk('public')->exists($oldPath));

        $newFile = UploadedFile::fake()->image('new.jpg');

        $resp = $this->actingAs($admin)->postJson('/api/admin/landing-contents/'.$item->id, [
            '_method' => 'PUT',
            'name' => 'Nombre actualizado',
            'file_url' => $newFile,
            'position' => 2,
            'landing_content_type_id' => $type->id,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json();
        $data = $data['data'];
        $this->assertNotEquals($oldPath, $data['file_url']);
        $this->assertTrue(Storage::disk('public')->exists($data['file_url']));
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
    }

    public function test_destroy_deletes_file()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['auth_method_id' => 2]);

        $type = LandingContentType::factory()->create();

        $file = UploadedFile::fake()->image('delete.jpg');
        $path = $file->store('landing', 'public');

        $item = LandingContent::factory()->create([
            'file_url' => $path,
            'landing_content_type_id' => $type->id,
        ]);

        $this->assertTrue(Storage::disk('public')->exists($path));

        $resp = $this->actingAs($admin)->delete('/api/admin/landing-contents/'.$item->id, ['deleted_reason' => 'Prueba eliminación']);

        $resp->assertStatus(200);
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertSoftDeleted('landing_contents', ['id' => $item->id]);
    }
}
