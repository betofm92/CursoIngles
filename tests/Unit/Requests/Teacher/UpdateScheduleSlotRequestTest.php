<?php

namespace Tests\Unit\Requests\Teacher;

use App\Http\Requests\Teacher\UpdateScheduleSlotRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateScheduleSlotRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorize_allows_only_teacher_users(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacherRequest = UpdateScheduleSlotRequest::create('/profesor/horarios/1', 'PUT');
        $teacherRequest->setUserResolver(fn () => $teacher);

        $adminRequest = UpdateScheduleSlotRequest::create('/profesor/horarios/1', 'PUT');
        $adminRequest->setUserResolver(fn () => $admin);

        $this->assertTrue($teacherRequest->authorize());
        $this->assertFalse($adminRequest->authorize());
    }

    public function test_rules_validate_required_fields_relations_and_time_range(): void
    {
        [$classroom, $topic] = $this->createClassroomAndTopic();
        $request = new UpdateScheduleSlotRequest();

        $valid = Validator::make([
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 5,
            'starts_at' => '15:00',
            'ends_at' => '17:00',
            'notes' => 'Actualizacion de prueba',
        ], $request->rules());
        $this->assertFalse($valid->fails());

        $invalid = Validator::make([
            'classroom_id' => $classroom->id,
            'course_topic_id' => 999999,
            'day_of_week' => 0,
            'starts_at' => '17:00',
            'ends_at' => '16:00',
            'notes' => str_repeat('a', 501),
        ], $request->rules());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('course_topic_id', $invalid->errors()->toArray());
        $this->assertArrayHasKey('day_of_week', $invalid->errors()->toArray());
        $this->assertArrayHasKey('ends_at', $invalid->errors()->toArray());
        $this->assertArrayHasKey('notes', $invalid->errors()->toArray());
    }

    /**
     * @return array{0: Classroom, 1: CourseTopic}
     */
    private function createClassroomAndTopic(): array
    {
        $course = Course::create([
            'code' => 'REQ-TS-UPDATE',
            'name' => 'Ingles Request Update',
            'is_active' => true,
        ]);

        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Topic Request Update',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'code' => 'REQ-ROOM-UPDATE',
            'name' => 'Aula Request Update',
            'capacity' => 8,
            'is_active' => true,
        ]);

        return [$classroom, $topic];
    }
}

