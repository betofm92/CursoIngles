<?php

namespace Tests\Unit\Controllers\Admin;

use App\Http\Controllers\Admin\ScheduleSlotController;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\ScheduleSlot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleSlotControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Carbon::setTestNow('2026-02-09 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_index_returns_confirmed_and_closed_slots_and_student_catalog(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('profesor');

        [$classroom, $topic] = $this->createClassroomAndTopic();

        $confirmed = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        $closed = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_CLOSED,
            'confirmed_at' => now(),
        ]);
        ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '12:00',
            'ends_at' => '14:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        $view = (new ScheduleSlotController())->index();
        $data = $view->getData();

        $this->assertSame('admin.schedule-slots.index', $view->name());
        $this->assertEqualsCanonicalizing(
            [$confirmed->id, $closed->id],
            $data['slots']->pluck('id')->all()
        );
        $this->assertSame([$student->id], $data['students']->pluck('id')->all());
        $this->assertNotContains($otherUser->id, $data['students']->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$teacher->id, $otherUser->id],
            $data['teachers']->pluck('id')->all()
        );
        $this->assertSame([$classroom->id], $data['classrooms']->pluck('id')->all());
        $this->assertSame('Lunes', $data['dayOptions'][1]);
        $this->assertSame(1, $data['activeDay']);
        $this->assertCount(2, $data['weekScopes']);
        $this->assertSame('current', $data['weekScopes'][0]['key']);
        $this->assertSame('next', $data['weekScopes'][1]['key']);
        $this->assertCount(6, $data['weekScopes'][0]['days']);
        $this->assertSame('Lunes', $data['weekScopes'][0]['days'][0]['label']);
        $this->assertEqualsCanonicalizing(
            [$confirmed->id],
            $data['slotsByScope']['current'][1]->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$closed->id],
            $data['slotsByScope']['current'][2]->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$confirmed->id],
            $data['slotsByScope']['next'][1]->pluck('id')->all()
        );
    }

    /**
     * @return array{0: Classroom, 1: CourseTopic}
     */
    private function createClassroomAndTopic(): array
    {
        $course = Course::create([
            'code' => 'ADMIN-SLOTS-COURSE',
            'name' => 'Ingles Admin Slots',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Admin Slots Topic',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'ADMIN-SLOTS-ROOM',
            'name' => 'Aula Admin Slots',
            'capacity' => 8,
            'is_active' => true,
        ]);

        return [$classroom, $topic];
    }
}
