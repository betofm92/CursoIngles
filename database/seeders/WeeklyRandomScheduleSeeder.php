<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class WeeklyRandomScheduleSeeder extends Seeder
{
    private const NOTE_PREFIX = 'Seeder semanal aleatorio:';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $weekSeed = (int) now()->format('oW');
        fake()->seed($weekSeed);

        $admin = User::firstOrCreate(
            ['email' => 'admin@cursoingles.test'],
            [
                'name' => 'Admin Instituto',
                'password' => Hash::make('password'),
            ],
        );
        $admin->syncRoles(['admin']);

        $teachers = $this->seedTeachers();
        $students = $this->seedStudents();
        $classrooms = $this->seedClassrooms();
        $courses = $this->seedCourses();

        $topicsByCourse = [];
        foreach ($courses as $course) {
            $topicsByCourse[$course->id] = CourseTopic::query()
                ->where('course_id', $course->id)
                ->where('is_active', true)
                ->get();
        }

        $teacherByCourse = $courses->values()->mapWithKeys(function (Course $course, int $index) use ($teachers): array {
            return [$course->id => $teachers[$index % $teachers->count()]->id];
        });

        ScheduleSlot::query()
            ->where('notes', 'like', self::NOTE_PREFIX.'%')
            ->delete();

        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $targetSlots = 12;
        $created = collect();
        $attempts = 0;
        $maxAttempts = 900;

        while ($created->count() < $targetSlots && $attempts < $maxAttempts) {
            $attempts++;

            $dayOfWeek = random_int(1, 6);
            $course = $courses->random();
            /** @var Collection<int, CourseTopic> $topics */
            $topics = $topicsByCourse[$course->id];

            if ($topics->isEmpty()) {
                continue;
            }

            $topic = $topics->random();
            $teacherId = (int) $teacherByCourse[$course->id];
            $classroomId = (int) $classrooms->random()->id;

            $durationMinutes = Arr::random([60, 90, 120, 150, 180]);
            $latestStart = (20 * 60) - $durationMinutes;
            $startOptions = range(8 * 60, $latestStart, 30);

            if ($startOptions === []) {
                continue;
            }

            $startsAtMinutes = Arr::random($startOptions);
            $endsAtMinutes = $startsAtMinutes + $durationMinutes;
            $startsAt = $this->toTime($startsAtMinutes);
            $endsAt = $this->toTime($endsAtMinutes);

            $hasConflict = ScheduleSlot::query()
                ->where('day_of_week', $dayOfWeek)
                ->where('status', '!=', ScheduleSlot::STATUS_CLOSED)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->where(function ($query) use ($teacherId, $classroomId): void {
                    $query->where('teacher_id', $teacherId)
                        ->orWhere('classroom_id', $classroomId);
                })
                ->exists();

            if ($hasConflict) {
                continue;
            }

            $slotDate = $weekStart->copy()->addDays($dayOfWeek - 1);
            $created[] = ScheduleSlot::create([
                'teacher_id' => $teacherId,
                'classroom_id' => $classroomId,
                'course_topic_id' => $topic->id,
                'day_of_week' => $dayOfWeek,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => $slotDate->copy()->setTime(
                    intdiv($startsAtMinutes, 60),
                    $startsAtMinutes % 60
                ),
                'notes' => self::NOTE_PREFIX.$weekSeed,
            ]);
        }

        if ($created->count() < $targetSlots) {
            throw new RuntimeException('No se pudo generar la cantidad minima de cursos semanales aleatorios.');
        }

        foreach ($created as $slot) {
            $maxAssignable = min(8, $students->count());
            $assignedCount = random_int(2, $maxAssignable);
            $assignedStudents = $students->shuffle()->take($assignedCount);

            foreach ($assignedStudents as $student) {
                Enrollment::create([
                    'schedule_slot_id' => $slot->id,
                    'student_id' => $student->id,
                    'created_by' => $admin->id,
                ]);
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function seedTeachers(): Collection
    {
        return collect([
            ['name' => 'Prof. Camila Soto', 'email' => 'teacher.random.1@cursoingles.test'],
            ['name' => 'Prof. Bruno Leon', 'email' => 'teacher.random.2@cursoingles.test'],
            ['name' => 'Prof. Valentina Rios', 'email' => 'teacher.random.3@cursoingles.test'],
            ['name' => 'Prof. Marcos Arias', 'email' => 'teacher.random.4@cursoingles.test'],
        ])->map(function (array $teacherData) {
            $teacher = User::firstOrCreate(
                ['email' => $teacherData['email']],
                [
                    'name' => $teacherData['name'],
                    'password' => Hash::make('password'),
                ],
            );
            $teacher->syncRoles(['profesor']);

            return $teacher;
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function seedStudents(): Collection
    {
        $students = collect(range(1, 24))->map(function (int $index): User {
            $student = User::firstOrCreate(
                ['email' => sprintf('student.random.%d@cursoingles.test', $index)],
                [
                    'name' => sprintf('Estudiante Random %d', $index),
                    'password' => Hash::make('password'),
                ],
            );
            $student->syncRoles(['estudiante']);

            return $student;
        });

        return $students->values();
    }

    /**
     * @return Collection<int, Classroom>
     */
    private function seedClassrooms(): Collection
    {
        Classroom::query()->whereNotIn('code', ['A1', 'A2', 'B1', 'B2'])->update(['is_active' => false]);

        return collect([
            ['code' => 'A1', 'name' => 'Aula A1', 'location' => 'Primer piso'],
            ['code' => 'A2', 'name' => 'Aula A2', 'location' => 'Primer piso'],
            ['code' => 'B1', 'name' => 'Aula B1', 'location' => 'Segundo piso'],
            ['code' => 'B2', 'name' => 'Aula B2', 'location' => 'Segundo piso'],
        ])->map(function (array $classroomData): Classroom {
            return Classroom::updateOrCreate(
                ['code' => $classroomData['code']],
                [
                    'name' => $classroomData['name'],
                    'location' => $classroomData['location'],
                    'capacity' => 8,
                    'is_active' => true,
                ],
            );
        })->values();
    }

    /**
     * @return Collection<int, Course>
     */
    private function seedCourses(): Collection
    {
        $catalog = [
            ['code' => 'WEEK-A1', 'name' => 'Ingles A1 Semanal', 'topic' => 'Basic Introductions'],
            ['code' => 'WEEK-A2', 'name' => 'Ingles A2 Semanal', 'topic' => 'Daily Life Dialogues'],
            ['code' => 'WEEK-B1', 'name' => 'Ingles B1 Semanal', 'topic' => 'Narrative Tenses'],
            ['code' => 'WEEK-B2', 'name' => 'Ingles B2 Semanal', 'topic' => 'Debate and Argumentation'],
            ['code' => 'WEEK-CONV', 'name' => 'Ingles Conversacional', 'topic' => 'Fluency Workshops'],
            ['code' => 'WEEK-BUS', 'name' => 'Ingles de Negocios', 'topic' => 'Meetings and Negotiations'],
            ['code' => 'WEEK-GRAM', 'name' => 'Gramatica Aplicada', 'topic' => 'Grammar in Context'],
            ['code' => 'WEEK-EXAM', 'name' => 'Preparacion de Examen', 'topic' => 'Exam Strategies'],
        ];

        return collect($catalog)->map(function (array $courseData): Course {
            $course = Course::updateOrCreate(
                ['code' => $courseData['code']],
                [
                    'name' => $courseData['name'],
                    'description' => 'Curso generado automaticamente para la semana actual.',
                    'is_active' => true,
                ],
            );

            CourseTopic::updateOrCreate(
                ['course_id' => $course->id, 'title' => $courseData['topic']],
                ['description' => 'Tema generado automaticamente.', 'is_active' => true],
            );

            return $course;
        })->values();
    }

    private function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}

