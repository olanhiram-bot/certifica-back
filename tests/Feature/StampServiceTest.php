<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuthMethod;
use App\Models\CourseCall;
use App\Models\Section;
use App\Models\Stamp;
use App\Models\StampActivity;
use App\Models\StampActivityStatus;
use App\Models\StampStatus;
use App\Models\User;
use App\Services\StampService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampServiceTest extends TestCase
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

    public function test_is_section_complete_marks_all_activities_in_review_when_complete()
    {
        User::factory()->create();
        $section = Section::factory()->create();
        $activities = Activity::factory()->count(3)->for($section)->create();

        $stamp = Stamp::factory()->create();

        // create stamp_activity records marked as completed (status 3)
        foreach ($activities as $activity) {
            StampActivity::create([
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
                'stamp_activity_status_id' => 3,
            ]);
        }

        $firstStampActivity = StampActivity::where('stamp_id', $stamp->id)->first();

        $service = $this->app->make(StampService::class);
        $service->isSectionComplete($firstStampActivity);

        $this->assertDatabaseHas('stamp_activities', [
            'stamp_id' => $stamp->id,
            'stamp_activity_status_id' => 4,
        ]);
    }

    public function test_is_section_complete_does_nothing_when_not_complete()
    {
        $section = Section::factory()->create();
        $activities = Activity::factory()->count(3)->for($section)->create();

        // dd(CourseCall::all());
        $stamp = Stamp::factory()->create();
        // only complete two out of three
        foreach ($activities->take(2) as $activity) {
            StampActivity::create([
                'stamp_id' => $stamp->id,
                'activity_id' => $activity->id,
                'stamp_activity_status_id' => 3,
            ]);
        }

        // add the remaining activity as not completed (status 1)
        StampActivity::create([
            'stamp_id' => $stamp->id,
            'activity_id' => $activities->last()->id,
            'stamp_activity_status_id' => 1,
        ]);

        $firstStampActivity = StampActivity::where('stamp_id', $stamp->id)->first();

        $service = $this->app->make(StampService::class);
        $service->isSectionComplete($firstStampActivity);

        // ensure no activity was changed to status 4
        $this->assertDatabaseMissing('stamp_activities', [
            'stamp_id' => $stamp->id,
            'stamp_activity_status_id' => 4,
        ]);
    }
}
