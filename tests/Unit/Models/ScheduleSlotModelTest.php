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

class ScheduleSlotModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_day_options_and_day_label_accessor_work(): void
    {
        $this->assertSame('Lunes', ScheduleSlot::dayOptions()[1]);

        $slot = new ScheduleSlot(['day_of_week' => 1]);
        $this->assertSame('Lunes', $slot->day_label);

        $unknownDaySlot = new ScheduleSlot(['day_of_week' => 9]);
        $this->assertSame('No definido', $unknownDaySlot->day_label);
    }

    public function test_capacity_accessor_and_available_seats_are_calculated_correctly(): void
    {
        [$slot, $studentA, $studentB, $studentC] = $this->createSlotWithClassroomCapacity(12);

        Enrollment::create([
            'schedule_slot_id' => $slot->id,
            'student_id' => $studentA->id,
        ]);
        Enrollment::create([
            'schedule_slot_id' => $slot->id,
            'student_id' => $studentB->id,
        ]);
        Enrollment::create([
            'schedule_slot_id' => $slot->id,
            'student_id' => $studentC->id,
        ]);

        $slot = ScheduleSlot::query()
            ->with('classroom')
            ->withCount('enrollments')
            ->findOrFail($slot->id);

        $this->assertSame(8, $slot->capacity);
        $this->assertSame(5, $slot->available_seats);

        $slotWithoutClassroom = new ScheduleSlot();
        $this->assertSame(8, $slotWithoutClassroom->capacity);
    }

    public function test_schedule_slot_relationships_to_teacher_classroom_topic_and_students(): void
    {
        [$slot, $studentA] = $this->createSlotWithClassroomCapacity(8);

        Enrollment::create([
            'schedule_slot_id' => $slot->id,
            'student_id' => $studentA->id,
        ]);

        $slot->load(['teacher', 'classroom', 'courseTopic', 'students']);

        $this->assertInstanceOf(User::class, $slot->teacher);
        $this->assertInstanceOf(Classroom::class, $slot->classroom);
        $this->assertInstanceOf(CourseTopic::class, $slot->courseTopic);
        $this->assertCount(1, $slot->students);
        $this->assertSame($studentA->id, $slot->students->first()->id);
    }

    /**
     * @return array{0: ScheduleSlot, 1: User, 2: User, 3: User}
     */
    private function createSlotWithClassroomCapacity(int $capacity): array
    {
        $teacher = User::factory()->create();
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();
        $studentC = User::factory()->create();

        $course = Course::create([
            'code' => 'ING-SLOT-U1',
            'name' => 'Ingles Slot U1',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Conversations',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'SLOT-U1',
            'name' => 'Aula Slot U1',
            'capacity' => $capacity,
            'is_active' => true,
        ]);

        $slot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 4,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return [$slot, $studentA, $studentB, $studentC];
    }
}
