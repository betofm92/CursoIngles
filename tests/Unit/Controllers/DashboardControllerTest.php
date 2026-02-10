<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\DashboardController;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Carbon::setTestNow('2026-02-09 09:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_dashboard_returns_expected_stats_and_slots(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacherA = User::factory()->create();
        $teacherA->assignRole('profesor');
        $teacherB = User::factory()->create();
        $teacherB->assignRole('profesor');

        $studentA = User::factory()->create();
        $studentA->assignRole('estudiante');
        $studentB = User::factory()->create();
        $studentB->assignRole('estudiante');

        [$classroom, $topic] = $this->createClassroomAndTopic();

        ScheduleSlot::create([
            'teacher_id' => $teacherA->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        ScheduleSlot::create([
            'teacher_id' => $teacherB->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $admin);

        $view = (new DashboardController())($request);
        $data = $view->getData();

        $this->assertSame('dashboard', $view->name());
        $this->assertSame('admin', $data['role']);
        $this->assertSame(2, $data['stats']['professors']);
        $this->assertSame(2, $data['stats']['students']);
        $this->assertSame(1, $data['stats']['confirmed_slots']);
        $this->assertSame(1, $data['stats']['pending_slots']);
        $this->assertCount(2, $data['slots']);
        $this->assertSame('Lunes', $data['todayDayLabel']);
        $this->assertCount(1, $data['todayCourses']);
        $this->assertSame('Ingles Dashboard', $data['todayCourses']->first()->courseTopic->course->name);
        $this->assertSame($teacherA->name, $data['todayCourses']->first()->teacher->name);
    }

    public function test_teacher_dashboard_only_uses_own_slots_for_stats(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('profesor');

        [$classroom, $topic] = $this->createClassroomAndTopic();

        ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);
        ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        ScheduleSlot::create([
            'teacher_id' => $otherTeacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $teacher);

        $view = (new DashboardController())($request);
        $data = $view->getData();

        $this->assertSame('profesor', $data['role']);
        $this->assertSame(2, $data['stats']['total_slots']);
        $this->assertSame(1, $data['stats']['draft_slots']);
        $this->assertSame(1, $data['stats']['confirmed_slots']);
        $this->assertCount(2, $data['slots']);
        $this->assertSame('Lunes', $data['todayDayLabel']);
        $this->assertCount(1, $data['todayCourses']);
        $this->assertSame(ScheduleSlot::STATUS_DRAFT, $data['todayCourses']->first()->status);
    }

    public function test_student_dashboard_only_shows_confirmed_assigned_slots(): void
    {
        $student = User::factory()->create();
        $student->assignRole('estudiante');

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
            'starts_at' => '07:00',
            'ends_at' => '09:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        $confirmedUnassigned = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        $draftAssigned = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '11:00',
            'ends_at' => '13:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        Enrollment::create([
            'schedule_slot_id' => $confirmedAssigned->id,
            'student_id' => $student->id,
            'created_by' => $admin->id,
        ]);
        Enrollment::create([
            'schedule_slot_id' => $draftAssigned->id,
            'student_id' => $student->id,
            'created_by' => $admin->id,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $student);

        $view = (new DashboardController())($request);
        $data = $view->getData();

        $this->assertSame('estudiante', $data['role']);
        $this->assertSame(1, $data['stats']['assigned_slots']);
        $this->assertCount(1, $data['slots']);
        $this->assertSame($confirmedAssigned->id, $data['slots']->first()->id);
        $this->assertNotSame($confirmedUnassigned->id, $data['slots']->first()->id);
        $this->assertSame('Lunes', $data['todayDayLabel']);
        $this->assertCount(1, $data['todayCourses']);
        $this->assertSame($confirmedAssigned->id, $data['todayCourses']->first()->id);
    }

    /**
     * @return array{0: Classroom, 1: CourseTopic}
     */
    private function createClassroomAndTopic(): array
    {
        $course = Course::create([
            'code' => 'DASH-COURSE',
            'name' => 'Ingles Dashboard',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Dashboard Topic',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'DASH-ROOM',
            'name' => 'Aula Dashboard',
            'capacity' => 8,
            'is_active' => true,
        ]);

        return [$classroom, $topic];
    }
}
