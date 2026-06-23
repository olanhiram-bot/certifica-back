<?php

namespace Tests\Feature;

use App\Models\AuthMethod;
use App\Models\CourseCall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCallControllerTest extends TestCase
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

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/admin/course-call')
            ->assertStatus(401);
    }

    public function test_index_returns_all_course_calls_without_pagination(): void
    {
        CourseCall::factory()->count(3)->create();

        $resp = $this->actingAs($this->ssoAdmin())->getJson('/api/admin/course-call');

        $resp->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_index_returns_paginated_course_calls_when_page_param_is_present(): void
    {
        CourseCall::factory()->count(15)->create();

        $resp = $this->actingAs($this->ssoAdmin())->getJson('/api/admin/course-call?page=1');

        $resp->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(10, 'data');
    }

    public function test_store_requires_authentication(): void
    {
        $payload = [
            'name' => 'Convocatoria prueba',
            'vigency_start_date' => now()->format('Y-m-d'),
            'vigency_end_date' => now()->addDays(5)->format('Y-m-d'),
        ];

        $this->postJson('/api/admin/course-call', $payload)
            ->assertStatus(401);
    }

    public function test_store_creates_course_call_when_no_current_exists(): void
    {

        $payload = [
            'name' => 'Convocatoria nueva',
            'vigency_start_date' => now()->format('Y-m-d'),
            'vigency_end_date' => now()->addDays(10)->format('Y-m-d'),
        ];

        $resp = $this->actingAs($this->ssoAdmin())->postJson('/api/admin/course-call', $payload);

        $resp->assertStatus(201)->assertJsonPath('data.name', 'Convocatoria nueva');

        $this->assertDatabaseHas('course_calls', ['name' => 'Convocatoria nueva']);
    }

    public function test_store_fails_when_current_course_call_exists(): void
    {

        // create an active course call for today
        CourseCall::create([
            'name' => 'Activa',
            'vigency_start_date' => now()->subDay()->format('Y-m-d'),
            'vigency_end_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $payload = [
            'name' => 'Otra',
            'vigency_start_date' => now()->format('Y-m-d'),
            'vigency_end_date' => now()->addDays(5)->format('Y-m-d'),
        ];

        $resp = $this->actingAs($this->ssoAdmin())->postJson('/api/admin/course-call', $payload);

        $resp->assertStatus(400)->assertJsonPath('error', 'Ya existe una convocatoria vigente');
    }

    public function test_show_requires_authentication(): void
    {
        $call = CourseCall::factory()->create();

        $this->getJson("/api/admin/course-call/{$call->id}")
            ->assertStatus(401);
    }

    public function test_show_returns_correct_course_call(): void
    {
        $call = CourseCall::factory()->create();

        $resp = $this->actingAs($this->ssoAdmin())->getJson("/api/admin/course-call/{$call->id}");

        $resp->assertStatus(200)
            ->assertJsonPath('data.id', $call->id)
            ->assertJsonPath('data.name', $call->name);
    }

    public function test_destroy_requires_authentication(): void
    {
        $call = CourseCall::factory()->create();

        $this->deleteJson("/api/admin/course-call/{$call->id}")
            ->assertStatus(401);
    }

    public function test_destroy_deletes_course_call(): void
    {
        $call = CourseCall::factory()->create();

        $resp = $this->actingAs($this->ssoAdmin())->deleteJson("/api/admin/course-call/{$call->id}", ['deleted_reason' => 'Prueba eliminación']);
        $resp->assertStatus(204);

        $this->assertSoftDeleted('course_calls', ['id' => $call->id]);
    }
}
