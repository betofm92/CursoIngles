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

class AdminCourseCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_course_topic_confirmed_slot_and_initial_students(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $studentA = User::factory()->create();
        $studentA->assignRole('estudiante');
        $studentB = User::factory()->create();
        $studentB->assignRole('estudiante');

        $classroom = Classroom::create([
            'code' => 'ADMIN-COURSE-A1',
            'name' => 'Aula Admin Course A1',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.schedule-slots.courses.store'), [
                'course_code' => 'ING-ADMIN-001',
                'course_name' => 'Ingles Admin Creado',
                'course_description' => 'Curso creado por admin',
                'topic_title' => 'Tema Inicial',
                'topic_description' => 'Descripcion tema',
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => 2,
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'notes' => 'Creado desde asignaciones',
                'student_ids' => [$studentA->id, $studentB->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $course = Course::where('code', 'ING-ADMIN-001')->first();
        $this->assertNotNull($course);

        $topic = CourseTopic::where('course_id', $course->id)->where('title', 'Tema Inicial')->first();
        $this->assertNotNull($topic);

        $slot = ScheduleSlot::where('course_topic_id', $topic->id)->first();
        $this->assertNotNull($slot);
        $this->assertSame(ScheduleSlot::STATUS_CONFIRMED, $slot->status);

        $this->assertDatabaseHas('enrollments', [
            'schedule_slot_id' => $slot->id,
            'student_id' => $studentA->id,
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'schedule_slot_id' => $slot->id,
            'student_id' => $studentB->id,
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_create_course_when_teacher_has_time_overlap(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $classroomA = Classroom::create([
            'code' => 'ADMIN-COURSE-B1',
            'name' => 'Aula Admin Course B1',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $classroomB = Classroom::create([
            'code' => 'ADMIN-COURSE-B2',
            'name' => 'Aula Admin Course B2',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $course = Course::create([
            'code' => 'ING-BASE-ADMIN',
            'name' => 'Ingles Base Admin',
            'is_active' => true,
        ]);
        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Tema Base',
            'is_active' => true,
        ]);

        ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroomA->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.schedule-slots.index'))
            ->post(route('admin.schedule-slots.courses.store'), [
                'course_code' => 'ING-ADMIN-OVER',
                'course_name' => 'Ingles Admin Overlap',
                'topic_title' => 'Tema Overlap',
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroomB->id,
                'day_of_week' => 3,
                'starts_at' => '10:00',
                'ends_at' => '12:00',
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseMissing('courses', ['code' => 'ING-ADMIN-OVER']);
    }
}

