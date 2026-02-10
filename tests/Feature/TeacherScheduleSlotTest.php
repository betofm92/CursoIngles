<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\ScheduleSlot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherScheduleSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_and_confirm_schedule_slot(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $course = Course::create([
            'code' => 'ING-T1',
            'name' => 'Ingles Test 1',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Topic Test',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'TST-A1',
            'name' => 'Aula Test A1',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.schedule-slots.store'), [
                'classroom_id' => $classroom->id,
                'course_topic_id' => $topic->id,
                'day_of_week' => 1,
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'notes' => 'Prueba',
            ])
            ->assertRedirect();

        $slot = ScheduleSlot::first();
        $this->assertNotNull($slot);
        $this->assertSame(ScheduleSlot::STATUS_DRAFT, $slot->status);

        $this->actingAs($teacher)
            ->patch(route('teacher.schedule-slots.confirm', $slot))
            ->assertRedirect();

        $slot->refresh();
        $this->assertSame(ScheduleSlot::STATUS_CONFIRMED, $slot->status);
        $this->assertNotNull($slot->confirmed_at);
    }
}
