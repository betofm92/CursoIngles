<?php

namespace Tests\Unit\Models;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_password_is_hashed_with_cast(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'cast.password@example.test',
            'password' => 'plain-password',
        ]);

        $this->assertNotSame('plain-password', $user->password);
        $this->assertTrue(Hash::check('plain-password', $user->password));
    }

    public function test_user_relationships_for_teaching_and_assignments_work(): void
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $admin = User::factory()->create();

        $course = Course::create([
            'code' => 'ING-REL-U1',
            'name' => 'Ingles Relacional U1',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Introductions',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'REL-U1',
            'name' => 'Aula Rel U1',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $slot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $enrollment = Enrollment::create([
            'schedule_slot_id' => $slot->id,
            'student_id' => $student->id,
            'created_by' => $admin->id,
        ]);

        $this->assertTrue($teacher->teachingSlots()->whereKey($slot->id)->exists());
        $this->assertTrue($student->enrollments()->whereKey($enrollment->id)->exists());
        $this->assertTrue($student->assignedSlots()->whereKey($slot->id)->exists());
    }
}
