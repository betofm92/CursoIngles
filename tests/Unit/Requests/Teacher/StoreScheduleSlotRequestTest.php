<?php

namespace Tests\Unit\Requests\Teacher;

use App\Http\Requests\Teacher\StoreScheduleSlotRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreScheduleSlotRequestTest extends TestCase
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

        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $teacherRequest = StoreScheduleSlotRequest::create('/profesor/horarios', 'POST');
        $teacherRequest->setUserResolver(fn () => $teacher);

        $studentRequest = StoreScheduleSlotRequest::create('/profesor/horarios', 'POST');
        $studentRequest->setUserResolver(fn () => $student);

        $this->assertTrue($teacherRequest->authorize());
        $this->assertFalse($studentRequest->authorize());
    }

    public function test_rules_validate_required_fields_relations_and_time_range(): void
    {
        [$classroom, $topic] = $this->createClassroomAndTopic();
        $request = new StoreScheduleSlotRequest();

        $valid = Validator::make([
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'notes' => 'Clase de prueba',
        ], $request->rules());
        $this->assertFalse($valid->fails());

        $invalid = Validator::make([
            'classroom_id' => 999999,
            'course_topic_id' => $topic->id,
            'day_of_week' => 9,
            'starts_at' => '10:00',
            'ends_at' => '09:00',
        ], $request->rules());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('classroom_id', $invalid->errors()->toArray());
        $this->assertArrayHasKey('day_of_week', $invalid->errors()->toArray());
        $this->assertArrayHasKey('ends_at', $invalid->errors()->toArray());
    }

    /**
     * @return array{0: Classroom, 1: CourseTopic}
     */
    private function createClassroomAndTopic(): array
    {
        $course = Course::create([
            'code' => 'REQ-TS-STORE',
            'name' => 'Ingles Request Store',
            'is_active' => true,
        ]);

        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Topic Request Store',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'code' => 'REQ-ROOM-STORE',
            'name' => 'Aula Request Store',
            'capacity' => 8,
            'is_active' => true,
        ]);

        return [$classroom, $topic];
    }
}

