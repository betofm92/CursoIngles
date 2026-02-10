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

class AdminEnrollmentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_only_to_confirmed_slots(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $course = Course::create([
            'code' => 'ING-A1-T',
            'name' => 'Ingles A1 Test',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Greetings',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'ROOM-T1',
            'name' => 'Room T1',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $confirmedSlot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $draftSlot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.schedule-slots.enrollments.store', $confirmedSlot), [
                'student_id' => $student->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'schedule_slot_id' => $confirmedSlot->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.schedule-slots.enrollments.store', $draftSlot), [
                'student_id' => $student->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('enrollments', [
            'schedule_slot_id' => $draftSlot->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_admin_cannot_exceed_capacity_of_eight_students_per_slot(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $course = Course::create([
            'code' => 'ING-A2-T',
            'name' => 'Ingles A2 Test',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Past Simple',
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'code' => 'ROOM-T2',
            'name' => 'Room T2',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $slot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $students = User::factory()->count(9)->create();
        foreach ($students as $student) {
            $student->assignRole('estudiante');
        }

        foreach ($students->take(8) as $student) {
            $this->actingAs($admin)
                ->post(route('admin.schedule-slots.enrollments.store', $slot), [
                    'student_id' => $student->id,
                ])
                ->assertRedirect();
        }

        $ninthStudent = $students->last();
        $this->actingAs($admin)
            ->post(route('admin.schedule-slots.enrollments.store', $slot), [
                'student_id' => $ninthStudent->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('enrollments', 8);
        $this->assertDatabaseMissing('enrollments', [
            'schedule_slot_id' => $slot->id,
            'student_id' => $ninthStudent->id,
        ]);
    }
}
