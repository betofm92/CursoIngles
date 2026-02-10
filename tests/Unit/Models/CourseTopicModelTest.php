<?php

namespace Tests\Unit\Models;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTopicModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_topic_casts_is_active_and_exposes_relationships(): void
    {
        $course = Course::create([
            'code' => 'ING-TOPIC-U1',
            'name' => 'Ingles Topic U1',
            'is_active' => true,
        ]);

        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Past Simple',
            'description' => 'Tema de pasado simple',
            'is_active' => 0,
        ]);

        $teacher = User::factory()->create();
        $classroom = Classroom::create([
            'code' => 'TOP-U1',
            'name' => 'Aula Topic U1',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $slot = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        $topic->load(['course', 'scheduleSlots']);

        $this->assertFalse($topic->is_active);
        $this->assertSame($course->id, $topic->course->id);
        $this->assertTrue($topic->scheduleSlots->contains($slot));
    }
}
