<?php

namespace Tests\Unit\Controllers\Teacher;

use App\Http\Controllers\Teacher\ScheduleSlotController;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\ScheduleSlot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ScheduleSlotControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_index_returns_teacher_slots_active_catalog_and_day_options(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('profesor');

        $activeCourse = Course::create([
            'code' => 'TEACHER-COURSE-ACTIVE',
            'name' => 'Ingles Teacher Active',
            'is_active' => true,
        ]);
        $inactiveCourse = Course::create([
            'code' => 'TEACHER-COURSE-INACTIVE',
            'name' => 'Ingles Teacher Inactive',
            'is_active' => false,
        ]);

        $activeTopic = CourseTopic::create([
            'course_id' => $activeCourse->id,
            'title' => 'Active Topic',
            'is_active' => true,
        ]);
        CourseTopic::create([
            'course_id' => $activeCourse->id,
            'title' => 'Inactive Topic',
            'is_active' => false,
        ]);
        CourseTopic::create([
            'course_id' => $inactiveCourse->id,
            'title' => 'Active But Inactive Course Topic',
            'is_active' => true,
        ]);

        $activeClassroom = Classroom::create([
            'code' => 'TEACHER-ROOM-ACTIVE',
            'name' => 'Aula Teacher Active',
            'capacity' => 8,
            'is_active' => true,
        ]);
        Classroom::create([
            'code' => 'TEACHER-ROOM-INACTIVE',
            'name' => 'Aula Teacher Inactive',
            'capacity' => 8,
            'is_active' => false,
        ]);

        $slotA = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $activeClassroom->id,
            'course_topic_id' => $activeTopic->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);
        $slotB = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $activeClassroom->id,
            'course_topic_id' => $activeTopic->id,
            'day_of_week' => 2,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        ScheduleSlot::create([
            'teacher_id' => $otherTeacher->id,
            'classroom_id' => $activeClassroom->id,
            'course_topic_id' => $activeTopic->id,
            'day_of_week' => 3,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $request = Request::create('/profesor/horarios', 'GET');
        $request->setUserResolver(fn () => $teacher);

        $view = (new ScheduleSlotController())->index($request);
        $data = $view->getData();

        $this->assertSame('teacher.schedule-slots.index', $view->name());
        $this->assertEqualsCanonicalizing([$slotA->id, $slotB->id], $data['slots']->pluck('id')->all());
        $this->assertCount(1, $data['classrooms']);
        $this->assertSame($activeClassroom->id, $data['classrooms']->first()->id);
        $this->assertCount(1, $data['topics']);
        $this->assertSame($activeTopic->id, $data['topics']->first()->id);
        $this->assertSame('Lunes', $data['dayOptions'][1]);
    }
}

