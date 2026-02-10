<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\CourseTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_casts_is_active_and_has_topics_relationship(): void
    {
        $course = Course::create([
            'code' => 'ING-COURSE-U1',
            'name' => 'Ingles Curso U1',
            'description' => 'Curso de prueba',
            'is_active' => 1,
        ]);

        $course->topics()->create([
            'title' => 'Greetings',
            'description' => 'Tema de saludos',
            'is_active' => true,
        ]);

        $course->refresh()->load('topics');

        $this->assertTrue($course->is_active);
        $this->assertCount(1, $course->topics);
        $this->assertInstanceOf(CourseTopic::class, $course->topics->first());
    }
}
