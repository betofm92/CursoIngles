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

    private const TARGET_SLOTS = 20;

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
        $students = $this->seedStudents(25);
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
        $statuses = $this->buildStatusPlan()->shuffle()->values();
        $created = collect();
        $attempts = 0;
        $maxAttempts = 3000;

        while ($created->count() < self::TARGET_SLOTS && $attempts < $maxAttempts) {
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

            $status = (string) $statuses[$created->count()];
            $slotDate = $weekStart->copy()->addDays($dayOfWeek - 1);

            $confirmedAt = match ($status) {
                ScheduleSlot::STATUS_DRAFT => null,
                ScheduleSlot::STATUS_CONFIRMED => $slotDate->copy()->setTime(
                    intdiv($startsAtMinutes, 60),
                    $startsAtMinutes % 60
                ),
                ScheduleSlot::STATUS_CLOSED => $slotDate->copy()->setTime(
                    intdiv($endsAtMinutes, 60),
                    $endsAtMinutes % 60
                ),
                default => null,
            };

            $created[] = ScheduleSlot::create([
                'teacher_id' => $teacherId,
                'classroom_id' => $classroomId,
                'course_topic_id' => $topic->id,
                'day_of_week' => $dayOfWeek,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $status,
                'confirmed_at' => $confirmedAt,
                'notes' => self::NOTE_PREFIX.$weekSeed,
            ]);
        }

        if ($created->count() < self::TARGET_SLOTS) {
            throw new RuntimeException('No se pudo generar la cantidad minima de cursos semanales aleatorios.');
        }

        foreach ($created as $slot) {
            if ($slot->status === ScheduleSlot::STATUS_DRAFT) {
                continue;
            }

            $maxAssignable = min(8, $students->count());
            $targetAssigned = random_int(2, $maxAssignable);
            $assigned = 0;

            foreach ($students->shuffle() as $student) {
                if ($assigned >= $targetAssigned) {
                    break;
                }

                if ($this->studentHasOverlap($student, $slot)) {
                    continue;
                }

                Enrollment::firstOrCreate([
                    'schedule_slot_id' => $slot->id,
                    'student_id' => $student->id,
                ], [
                    'created_by' => $admin->id,
                ]);

                $assigned++;
            }
        }
    }

    /**
     * @return Collection<int, string>
     */
    private function buildStatusPlan(): Collection
    {
        return collect([
            ...array_fill(0, 7, ScheduleSlot::STATUS_DRAFT),
            ...array_fill(0, 8, ScheduleSlot::STATUS_CONFIRMED),
            ...array_fill(0, 5, ScheduleSlot::STATUS_CLOSED),
        ]);
    }

    private function studentHasOverlap(User $student, ScheduleSlot $slot): bool
    {
        return ScheduleSlot::query()
            ->join('enrollments', 'enrollments.schedule_slot_id', '=', 'schedule_slots.id')
            ->where('enrollments.student_id', $student->id)
            ->where('schedule_slots.day_of_week', $slot->day_of_week)
            ->where('schedule_slots.starts_at', '<', $slot->ends_at)
            ->where('schedule_slots.ends_at', '>', $slot->starts_at)
            ->whereIn('schedule_slots.status', [ScheduleSlot::STATUS_CONFIRMED, ScheduleSlot::STATUS_CLOSED])
            ->exists();
    }

    /**
     * @return Collection<int, User>
     */
    private function seedTeachers(): Collection
    {
        $teacherCatalog = [
            ['name' => 'Msc. Wilson Sarmiento', 'email' => 'wilson.sarmiento@cursoingles.test'],
            ['name' => 'Miss Mishel Medina', 'email' => 'mishel.medina@cursoingles.test'],
            ['name' => 'Miss Ximena Bravo', 'email' => 'ximena.bravo@cursoingles.test'],
            ['name' => 'Miss Pauleth Torres', 'email' => 'pauleth.torres@cursoingles.test'],
            ['name' => 'Miss Fabiana Rivas', 'email' => 'fabiana.rivas@cursoingles.test'],
            ['name' => 'Mr. Wilson Tello', 'email' => 'wilson.tello@cursoingles.test'],
        ];

        return collect($teacherCatalog)->map(function (array $teacherData): User {
            $teacher = User::updateOrCreate(
                ['email' => $teacherData['email']],
                [
                    'name' => $teacherData['name'],
                    'password' => Hash::make('password'),
                ],
            );
            $teacher->syncRoles(['profesor']);

            return $teacher;
        })->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function seedStudents(int $count): Collection
    {
        $students = collect(range(1, $count))->map(function (int $index): User {
            $student = User::updateOrCreate(
                ['email' => sprintf('estudiante%02d@cursoingles.test', $index)],
                [
                    'name' => fake()->unique()->name(),
                    'password' => Hash::make('password'),
                ],
            );
            $student->syncRoles(['estudiante']);

            return $student;
        });

        fake()->unique(true);

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
            ['code' => 'WEEK-PRON', 'name' => 'Pronunciacion Intensiva', 'topic' => 'Pronunciation Accuracy'],
            ['code' => 'WEEK-READ', 'name' => 'Reading Club', 'topic' => 'Critical Reading Skills'],
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
