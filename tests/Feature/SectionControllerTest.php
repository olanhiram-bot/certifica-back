<?php

namespace Tests\Feature;

use App\Models\AuthMethod;
use App\Models\Section;
use App\Models\SectionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionControllerTest extends TestCase
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

    public function test_index_returns_sections_for_given_section_type()
    {
        $user = User::factory()->create(['auth_method_id' => 1]);
        $type = SectionType::factory()->create();
        Section::factory()->count(3)->create(['section_type_id' => $type->id]);

        $resp = $this->actingAs($user)->getJson("/api/sections-types/{$type->id}/sections");
        $resp->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_store_creates_section_under_section_type()
    {
        $type = SectionType::factory()->create();
        $admin = User::factory()->create(['auth_method_id' => 2]);

        $data = ['name' => 'Seccion de prueba'];

        $resp = $this->actingAs($admin)->postJson("/api/admin/sections-types/{$type->id}/sections", $data);

        $resp->assertStatus(201)->assertJsonFragment(['name' => 'Seccion de prueba']);
        $this->assertDatabaseHas('sections', ['name' => 'Seccion de prueba', 'section_type_id' => $type->id]);
    }

    public function test_update_and_destroy_work_with_shallow_routes()
    {
        $section = Section::factory()->create();
        $admin = User::factory()->create(['auth_method_id' => 2]);
        $updateData = ['name' => 'Actualizada', 'section_type_id' => $section->section_type_id];
        $resp = $this->actingAs($admin)->putJson("/api/admin/sections/{$section->id}", $updateData);
        $resp->assertStatus(200)->assertJsonFragment(['name' => 'Actualizada']);
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'name' => 'Actualizada']);
    }

    public function test_destroy_section()
    {
        $section = Section::factory()->create();
        $admin = User::factory()->create(['auth_method_id' => 2]);

        $resp = $this->actingAs($admin)->delete("/api/admin/sections/{$section->id}", ['deleted_reason' => 'Prueba eliminación']);

        $resp->assertStatus(204);
        $this->assertSoftDeleted('sections', ['id' => $section->id]);
    }
}
