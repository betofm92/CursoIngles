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

    public function test_different_teachers_can_create_courses_in_same_time_with_different_classrooms(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $teacherA = User::factory()->create();
        $teacherA->assignRole('profesor');

        $teacherB = User::factory()->create();
        $teacherB->assignRole('profesor');

        $courseA = Course::create([
            'code' => 'ING-SAME-1',
            'name' => 'Ingles Same Time 1',
            'is_active' => true,
        ]);
        $courseB = Course::create([
            'code' => 'ING-SAME-2',
            'name' => 'Ingles Same Time 2',
            'is_active' => true,
        ]);

        $topicA = CourseTopic::create([
            'course_id' => $courseA->id,
            'title' => 'Topic 1',
            'is_active' => true,
        ]);
        $topicB = CourseTopic::create([
            'course_id' => $courseB->id,
            'title' => 'Topic 2',
            'is_active' => true,
        ]);

        $classroomA = Classroom::create([
            'code' => 'SAME-A1',
            'name' => 'Aula A1',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $classroomB = Classroom::create([
            'code' => 'SAME-A2',
            'name' => 'Aula A2',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($teacherA)
            ->post(route('teacher.schedule-slots.store'), [
                'classroom_id' => $classroomA->id,
                'course_topic_id' => $topicA->id,
                'day_of_week' => 1,
                'starts_at' => '08:00',
                'ends_at' => '10:00',
            ])
            ->assertRedirect();

        $this->actingAs($teacherB)
            ->post(route('teacher.schedule-slots.store'), [
                'classroom_id' => $classroomB->id,
                'course_topic_id' => $topicB->id,
                'day_of_week' => 1,
                'starts_at' => '08:00',
                'ends_at' => '10:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('schedule_slots', 2);
    }

    public function test_course_cannot_be_assigned_to_two_different_teachers(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $teacherA = User::factory()->create();
        $teacherA->assignRole('profesor');

        $teacherB = User::factory()->create();
        $teacherB->assignRole('profesor');

        $course = Course::create([
            'code' => 'ING-ONE-TEACHER',
            'name' => 'Ingles One Teacher',
            'is_active' => true,
        ]);
        $topicA = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Topic A',
            'is_active' => true,
        ]);
        $topicB = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Topic B',
            'is_active' => true,
        ]);

        $classroomA = Classroom::create([
            'code' => 'ONE-T-A1',
            'name' => 'Aula One Teacher A1',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $classroomB = Classroom::create([
            'code' => 'ONE-T-A2',
            'name' => 'Aula One Teacher A2',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($teacherA)
            ->post(route('teacher.schedule-slots.store'), [
                'classroom_id' => $classroomA->id,
                'course_topic_id' => $topicA->id,
                'day_of_week' => 2,
                'starts_at' => '10:00',
                'ends_at' => '12:00',
            ])
            ->assertRedirect();

        $this->actingAs($teacherB)
            ->from(route('teacher.schedule-slots.index'))
            ->post(route('teacher.schedule-slots.store'), [
                'classroom_id' => $classroomB->id,
                'course_topic_id' => $topicB->id,
                'day_of_week' => 3,
                'starts_at' => '12:00',
                'ends_at' => '14:00',
            ])
            ->assertSessionHasErrors('course_topic_id');

        $this->assertDatabaseCount('schedule_slots', 1);
    }
}
