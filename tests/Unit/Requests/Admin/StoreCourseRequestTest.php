<?php

namespace Tests\Unit\Requests\Admin;

use App\Http\Requests\Admin\StoreCourseRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCourseRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorize_allows_only_admin_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $adminRequest = StoreCourseRequest::create('/admin/horarios/cursos', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);

        $teacherRequest = StoreCourseRequest::create('/admin/horarios/cursos', 'POST');
        $teacherRequest->setUserResolver(fn () => $teacher);

        $this->assertTrue($adminRequest->authorize());
        $this->assertFalse($teacherRequest->authorize());
    }

    public function test_rules_validate_required_fields_and_schedule_window(): void
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $classroom = Classroom::create([
            'code' => 'REQ-COURSE-ROOM',
            'name' => 'Aula Req Course',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $request = new StoreCourseRequest();

        $valid = Validator::make([
            'course_code' => 'REQ-COURSE-001',
            'course_name' => 'Curso de Prueba',
            'course_description' => 'Descripcion curso',
            'topic_title' => 'Tema Principal',
            'topic_description' => 'Descripcion tema',
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 2,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'notes' => 'Notas',
            'student_ids' => [$student->id],
        ], $request->rules());
        $this->assertFalse($valid->fails());

        $invalid = Validator::make([
            'course_code' => '',
            'course_name' => '',
            'topic_title' => '',
            'teacher_id' => 999999,
            'classroom_id' => 999999,
            'day_of_week' => 7,
            'starts_at' => '20:00',
            'ends_at' => '20:30',
            'student_ids' => [999999],
        ], $request->rules());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('course_code', $invalid->errors()->toArray());
        $this->assertArrayHasKey('course_name', $invalid->errors()->toArray());
        $this->assertArrayHasKey('topic_title', $invalid->errors()->toArray());
        $this->assertArrayHasKey('teacher_id', $invalid->errors()->toArray());
        $this->assertArrayHasKey('classroom_id', $invalid->errors()->toArray());
        $this->assertArrayHasKey('day_of_week', $invalid->errors()->toArray());
        $this->assertArrayHasKey('starts_at', $invalid->errors()->toArray());
        $this->assertArrayHasKey('ends_at', $invalid->errors()->toArray());
        $this->assertArrayHasKey('student_ids.0', $invalid->errors()->toArray());
    }

    public function test_rules_validate_unique_course_code_and_distinct_students(): void
    {
        Course::create([
            'code' => 'REQ-COURSE-EXISTING',
            'name' => 'Curso existente',
            'is_active' => true,
        ]);

        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $classroom = Classroom::create([
            'code' => 'REQ-COURSE-ROOM2',
            'name' => 'Aula Req Course 2',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $request = new StoreCourseRequest();

        $validator = Validator::make([
            'course_code' => 'REQ-COURSE-EXISTING',
            'course_name' => 'Nuevo Curso',
            'topic_title' => 'Nuevo Tema',
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 3,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'student_ids' => [$student->id, $student->id],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('course_code', $validator->errors()->toArray());
        $this->assertArrayHasKey('student_ids.1', $validator->errors()->toArray());
    }
}

