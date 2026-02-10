<?php

namespace Tests\Unit\Controllers\Student;

use App\Http\Controllers\Student\ScheduleSlotController;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
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

    public function test_index_only_returns_assigned_confirmed_or_closed_slots(): void
    {
        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('estudiante');

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$classroom, $topic] = $this->createClassroomAndTopic();

        $confirmedAssigned = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        $closedAssigned = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_CLOSED,
            'confirmed_at' => now(),
        ]);
        $draftAssigned = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '12:00',
            'ends_at' => '14:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        Enrollment::create([
            'schedule_slot_id' => $confirmedAssigned->id,
            'student_id' => $student->id,
            'created_by' => $admin->id,
        ]);
        Enrollment::create([
            'schedule_slot_id' => $closedAssigned->id,
            'student_id' => $student->id,
            'created_by' => $admin->id,
        ]);
        Enrollment::create([
            'schedule_slot_id' => $draftAssigned->id,
            'student_id' => $student->id,
            'created_by' => $admin->id,
        ]);

        // Enrollment for a different student must not leak in results.
        Enrollment::create([
            'schedule_slot_id' => $confirmedAssigned->id,
            'student_id' => $otherStudent->id,
            'created_by' => $admin->id,
        ]);

        $request = Request::create('/estudiante/horarios', 'GET');
        $request->setUserResolver(fn () => $student);

        $view = (new ScheduleSlotController())->index($request);
        $data = $view->getData();

        $this->assertSame('student.schedule-slots.index', $view->name());
        $this->assertCount(2, $data['slots']);
        $this->assertEqualsCanonicalizing(
            [$confirmedAssigned->id, $closedAssigned->id],
            $data['slots']->pluck('id')->all()
        );
    }

    /**
     * @return array{0: Classroom, 1: CourseTopic}
     */
    private function createClassroomAndTopic(): array
    {
        $course = Course::create([
            'code' => 'STUDENT-COURSE',
            'name' => 'Ingles Student',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Student Topic',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'STUDENT-ROOM',
            'name' => 'Aula Student',
            'capacity' => 8,
            'is_active' => true,
        ]);

        return [$classroom, $topic];
    }
}

