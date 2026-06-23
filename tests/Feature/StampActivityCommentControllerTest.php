<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuthMethod;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityComment;
use App\Models\StampActivityStatus;
use App\Models\StampStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampActivityCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AuthMethod::insert([['id' => 1, 'name' => 'django']]);
        StampActivityStatus::insert([
            ['id' => 1, 'name' => 'Pendiente', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Aprobado', 'created_at' => now(), 'updated_at' => now()],
        ]);
        StampStatus::insert([
            ['id' => 1, 'name' => 'Nuevo', 'hexadecimal' => '#9164CC'],
            ['id' => 2, 'name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['id' => 3, 'name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['id' => 4, 'name' => 'Aprobado', 'hexadecimal' => '#00BC70'],
            ['id' => 5, 'name' => 'Vencido', 'hexadecimal' => '#eb551a'],
            ['id' => 6, 'name' => 'Rechazado', 'hexadecimal' => '#FF4438'],
        ]);
    }

    private function createStampActivity(?User $user = null): StampActivity
    {
        $stamp = Stamp::factory()->create([
            'reviewer_id' => $user ? $user->id : null,
        ]);
        $activity = Activity::factory()->create();

        return StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activity->id,
            'stamp_activity_status_id' => 1,
        ]);
    }

    // -----------------------------------------------------------------------
    // index  GET /api/stamp-activities/{stamp_activity}/comment
    // -----------------------------------------------------------------------

    public function test_index_requires_authentication(): void
    {
        $stampActivity = $this->createStampActivity();

        $this->getJson("/api/stamp-activities/{$stampActivity->id}/comment")
            ->assertStatus(401);
    }

    public function test_index_returns_all_comments_for_stamp_activity(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();

        StampActivityComment::create(['stamp_activity_id' => $stampActivity->id, 'comment' => 'Comentario 1']);
        StampActivityComment::create(['stamp_activity_id' => $stampActivity->id, 'comment' => 'Comentario 2']);
        StampActivityComment::create(['stamp_activity_id' => $stampActivity->id, 'comment' => 'Comentario 3']);

        $resp = $this->actingAs($user)->getJson("/api/stamp-activities/{$stampActivity->id}/comment");

        $resp->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_index_returns_paginated_comments_when_page_param_is_present(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();

        for ($i = 0; $i < 15; $i++) {
            StampActivityComment::create([
                'stamp_activity_id' => $stampActivity->id,
                'comment' => "Comentario {$i}",
            ]);
        }

        $resp = $this->actingAs($user)->getJson("/api/stamp-activities/{$stampActivity->id}/comment?page=1");

        $resp->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(10, 'data');
    }

    public function test_index_only_returns_comments_for_given_stamp_activity(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();
        $otherStampActivity = $this->createStampActivity();

        StampActivityComment::create(['stamp_activity_id' => $stampActivity->id, 'comment' => 'Comentario propio']);
        StampActivityComment::create(['stamp_activity_id' => $otherStampActivity->id, 'comment' => 'Comentario ajeno']);

        $resp = $this->actingAs($user)->getJson("/api/stamp-activities/{$stampActivity->id}/comment");

        $resp->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_index_returns_empty_collection_when_stamp_activity_has_no_comments(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();

        $resp = $this->actingAs($user)->getJson("/api/stamp-activities/{$stampActivity->id}/comment");

        $resp->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_index_returns_404_for_nonexistent_stamp_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/stamp-activities/9999/comment')
            ->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // store  POST /api/stamp-activities/{stamp_activity}/comment
    // -----------------------------------------------------------------------

    public function test_store_requires_authentication(): void
    {
        $stampActivity = $this->createStampActivity();

        $this->postJson("/api/stamp-activities/{$stampActivity->id}/comment", [
            'comment' => 'Nuevo comentario',
        ])->assertStatus(401);
    }

    public function test_store_creates_comment(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity($user);

        $resp = $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/comment", [
            'comment' => 'Nuevo comentario',
        ]);

        $resp->assertStatus(201)
            ->assertJsonPath('data.comment', 'Nuevo comentario')
            ->assertJsonPath('data.stamp_activity_id', $stampActivity->id);

        $this->assertDatabaseHas('stamp_activity_comments', [
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'Nuevo comentario',
        ]);
    }

    public function test_store_creates_comment_un_reviwer_id(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();

        $resp = $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/comment", [
            'comment' => 'Nuevo comentario',
        ]);

        $resp->assertStatus(422)
            ->assertJsonPath('message', 'Solo el revisor asignado puede agregar comentarios');
    }

    public function test_store_requires_comment_field(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();

        $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/comment", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_store_returns_correct_json_structure(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity($user);

        $resp = $this->actingAs($user)->postJson("/api/stamp-activities/{$stampActivity->id}/comment", [
            'comment' => 'Comentario con estructura',
        ]);
        $resp->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'comment',
                    'stamp_activity_id',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_store_returns_404_for_nonexistent_stamp_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/stamp-activities/9999/comment', [
            'comment' => 'Comentario',
        ])->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // show  GET /api/comment/{comment}
    // -----------------------------------------------------------------------

    public function test_show_requires_authentication(): void
    {
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'Comentario',
        ]);

        $this->getJson("/api/comment/{$comment->id}")
            ->assertStatus(401);
    }

    public function test_show_returns_comment(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'Mi comentario',
        ]);

        $resp = $this->actingAs($user)->getJson("/api/comment/{$comment->id}");

        $resp->assertStatus(200)
            ->assertJsonPath('data.id', $comment->id)
            ->assertJsonPath('data.comment', 'Mi comentario')
            ->assertJsonPath('data.stamp_activity_id', $stampActivity->id);
    }

    public function test_show_returns_correct_json_structure(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'Estructura',
        ]);

        $resp = $this->actingAs($user)->getJson("/api/comment/{$comment->id}");

        $resp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'comment',
                    'stamp_activity_id',
                    'created_by',
                    'updated_by',
                    'deleted_by',
                    'deleted_at',
                    'deleted_reason',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_comment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/comment/9999')
            ->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // update  PUT /api/comment/{comment}
    // -----------------------------------------------------------------------

    public function test_update_requires_authentication(): void
    {
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'Original',
        ]);

        $this->putJson("/api/comment/{$comment->id}", [
            'comment' => 'Modificado',
        ])->assertStatus(401);
    }

    public function test_update_modifies_comment(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'Original',
        ]);

        $resp = $this->actingAs($user)->putJson("/api/comment/{$comment->id}", [
            'comment' => 'Comentario actualizado',
        ]);

        $resp->assertStatus(200)
            ->assertJsonPath('data.id', $comment->id)
            ->assertJsonPath('data.comment', 'Comentario actualizado');

        $this->assertDatabaseHas('stamp_activity_comments', [
            'id' => $comment->id,
            'comment' => 'Comentario actualizado',
        ]);
    }

    public function test_update_requires_comment_field(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'Original',
        ]);

        $this->actingAs($user)->putJson("/api/comment/{$comment->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_update_returns_404_for_nonexistent_comment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/comment/9999', [
            'comment' => 'Comentario',
        ])->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // destroy  DELETE /api/comment/{comment}
    // -----------------------------------------------------------------------

    public function test_destroy_requires_authentication(): void
    {
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'A eliminar',
        ]);

        $this->deleteJson("/api/comment/{$comment->id}")
            ->assertStatus(401);
    }

    public function test_destroy_soft_deletes_comment(): void
    {
        $user = User::factory()->create();
        $stampActivity = $this->createStampActivity();
        $comment = StampActivityComment::create([
            'stamp_activity_id' => $stampActivity->id,
            'comment' => 'A eliminar',
        ]);

        $this->actingAs($user)->deleteJson("/api/comment/{$comment->id}", ['deleted_reason' => 'Prueba eliminación'])
            ->assertStatus(204);

        $this->assertSoftDeleted('stamp_activity_comments', ['id' => $comment->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_comment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/comment/9999', ['deleted_reason' => 'Prueba eliminación'])
            ->assertStatus(404);
    }
}
