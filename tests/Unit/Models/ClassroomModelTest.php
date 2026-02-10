<?php

namespace Tests\Unit\Models;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_capacity_is_capped_at_eight(): void
    {
        $largeClassroom = Classroom::create([
            'code' => 'CAP-LARGE-U1',
            'name' => 'Aula Grande',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $smallClassroom = Classroom::create([
            'code' => 'CAP-SMALL-U1',
            'name' => 'Aula Pequena',
            'capacity' => 6,
            'is_active' => true,
        ]);

        $this->assertSame(8, $largeClassroom->effectiveCapacity());
        $this->assertSame(6, $smallClassroom->effectiveCapacity());
    }

    public function test_classroom_has_schedule_slots_relationship(): void
    {
        $teacher = User::factory()->create();
        $course = Course::create([
            'code' => 'ING-CLASSROOM-U1',
            'name' => 'Ingles Classroom U1',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Routine Verbs',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'CLASSROOM-U1',
            'name' => 'Aula Classroom U1',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $slot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '13:00',
            'ends_at' => '15:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $classroom->load('scheduleSlots');

        $this->assertTrue($classroom->scheduleSlots->contains($slot));
    }
}
