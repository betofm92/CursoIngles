<?php

namespace Tests\Unit\Models;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_relationships_work_for_slot_student_and_creator(): void
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $admin = User::factory()->create();

        $course = Course::create([
            'code' => 'ING-ENROLL-U1',
            'name' => 'Ingles Enrollment U1',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Business Speaking',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'ENROLL-U1',
            'name' => 'Aula Enrollment U1',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $slot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 5,
            'starts_at' => '16:00',
            'ends_at' => '18:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $enrollment = Enrollment::create([
            'schedule_slot_id' => $slot->id,
            'student_id' => $student->id,
            'created_by' => $admin->id,
        ]);

        $enrollment->load(['scheduleSlot', 'student', 'creator']);

        $this->assertSame($slot->id, $enrollment->scheduleSlot->id);
        $this->assertSame($student->id, $enrollment->student->id);
        $this->assertSame($admin->id, $enrollment->creator->id);
    }
}
