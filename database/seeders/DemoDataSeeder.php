<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        $courseA1 = Course::updateOrCreate(
            ['code' => 'ING-A1'],
            [
                'name' => 'Ingles A1',
                'description' => 'Nivel inicial para comunicacion basica.',
                'is_active' => true,
            ],
        );
        $courseA2 = Course::updateOrCreate(
            ['code' => 'ING-A2'],
            [
                'name' => 'Ingles A2',
                'description' => 'Consolidacion de estructuras y conversaciones cotidianas.',
                'is_active' => true,
            ],
        );
        $courseB1 = Course::updateOrCreate(
            ['code' => 'ING-B1'],
            [
                'name' => 'Ingles B1',
                'description' => 'Fluidez intermedia para contextos academicos y laborales.',
                'is_active' => true,
            ],
        );
        $courseB2 = Course::updateOrCreate(
            ['code' => 'ING-B2'],
            [
                'name' => 'Ingles B2',
                'description' => 'Comunicacion avanzada con enfoque academico.',
                'is_active' => true,
            ],
        );
        $courseConv = Course::updateOrCreate(
            ['code' => 'ING-CONV'],
            [
                'name' => 'Ingles Conversacional',
                'description' => 'Practica intensiva de speaking, debate y expresion fluida.',
                'is_active' => true,
            ],
        );
        $courseBusiness = Course::updateOrCreate(
            ['code' => 'ING-BUS'],
            [
                'name' => 'Ingles de Negocios',
                'description' => 'Comunicacion en entornos corporativos.',
                'is_active' => true,
            ],
        );

        $topicGreetings = CourseTopic::updateOrCreate(
            ['course_id' => $courseA1->id, 'title' => 'Greetings and Introductions'],
            ['description' => 'Saludos, presentaciones y preguntas basicas.', 'is_active' => true],
        );
        $topicRoutines = CourseTopic::updateOrCreate(
            ['course_id' => $courseA1->id, 'title' => 'Daily Routines'],
            ['description' => 'Habitos y acciones frecuentes en presente simple.', 'is_active' => true],
        );
        $topicPast = CourseTopic::updateOrCreate(
            ['course_id' => $courseA2->id, 'title' => 'Past Simple and Stories'],
            ['description' => 'Narracion de experiencias en pasado.', 'is_active' => true],
        );
        $topicWork = CourseTopic::updateOrCreate(
            ['course_id' => $courseB1->id, 'title' => 'Workplace Communication'],
            ['description' => 'Reuniones, correos y conversaciones profesionales.', 'is_active' => true],
        );
        $topicPresentations = CourseTopic::updateOrCreate(
            ['course_id' => $courseB2->id, 'title' => 'Professional Presentations'],
            ['description' => 'Presentaciones de alto impacto en ingles.', 'is_active' => true],
        );
        $topicDebate = CourseTopic::updateOrCreate(
            ['course_id' => $courseConv->id, 'title' => 'Debate and Fluency Drills'],
            ['description' => 'Argumentacion y velocidad de respuesta en speaking.', 'is_active' => true],
        );
        $topicBusiness = CourseTopic::updateOrCreate(
            ['course_id' => $courseBusiness->id, 'title' => 'Meetings and Negotiations'],
            ['description' => 'Vocabulario practico para juntas y negociacion.', 'is_active' => true],
        );

        Classroom::query()->whereNotIn('code', ['A1', 'A2', 'B1', 'B2'])->update(['is_active' => false]);

        $classroomA1 = Classroom::updateOrCreate(
            ['code' => 'A1'],
            ['name' => 'Aula A1', 'location' => 'Primer piso', 'capacity' => 8, 'is_active' => true],
        );
        $classroomA2 = Classroom::updateOrCreate(
            ['code' => 'A2'],
            ['name' => 'Aula A2', 'location' => 'Primer piso', 'capacity' => 8, 'is_active' => true],
        );
        $classroomB1 = Classroom::updateOrCreate(
            ['code' => 'B1'],
            ['name' => 'Aula B1', 'location' => 'Segundo piso', 'capacity' => 8, 'is_active' => true],
        );
        $classroomB2 = Classroom::updateOrCreate(
            ['code' => 'B2'],
            ['name' => 'Aula B2', 'location' => 'Segundo piso', 'capacity' => 8, 'is_active' => true],
        );

        $slot1 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teachers[0]->id,
                'classroom_id' => $classroomA1->id,
                'day_of_week' => 1,
                'starts_at' => '08:00',
                'ends_at' => '10:00',
            ],
            [
                'course_topic_id' => $topicGreetings->id,
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => now()->subDays(5),
                'notes' => 'Grupo matutino inicial.',
            ],
        );

        $slot2 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teachers[1]->id,
                'classroom_id' => $classroomA2->id,
                'day_of_week' => 1,
                'starts_at' => '10:00',
                'ends_at' => '12:00',
            ],
            [
                'course_topic_id' => $topicPast->id,
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => now()->subDays(4),
                'notes' => 'Nivel A2 intensivo.',
            ],
        );

        $slot3 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teachers[2]->id,
                'classroom_id' => $classroomB1->id,
                'day_of_week' => 2,
                'starts_at' => '14:00',
                'ends_at' => '16:00',
            ],
            [
                'course_topic_id' => $topicWork->id,
                'status' => ScheduleSlot::STATUS_DRAFT,
                'confirmed_at' => null,
                'notes' => 'Pendiente de confirmacion docente.',
            ],
        );

        $slot4 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teachers[3]->id,
                'classroom_id' => $classroomB2->id,
                'day_of_week' => 3,
                'starts_at' => '16:00',
                'ends_at' => '18:00',
            ],
            [
                'course_topic_id' => $topicDebate->id,
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => now()->subDays(2),
                'notes' => 'Speaking avanzado con dinamicas de debate.',
            ],
        );

        $slot5 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teachers[4]->id,
                'classroom_id' => $classroomA1->id,
                'day_of_week' => 5,
                'starts_at' => '09:00',
                'ends_at' => '11:00',
            ],
            [
                'course_topic_id' => $topicPresentations->id,
                'status' => ScheduleSlot::STATUS_CLOSED,
                'confirmed_at' => now()->subDays(10),
                'notes' => 'Cohorte cerrada por cumplimiento de objetivos.',
            ],
        );

        $slot6 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teachers[5]->id,
                'classroom_id' => $classroomA2->id,
                'day_of_week' => 6,
                'starts_at' => '11:00',
                'ends_at' => '13:00',
            ],
            [
                'course_topic_id' => $topicBusiness->id,
                'status' => ScheduleSlot::STATUS_DRAFT,
                'confirmed_at' => null,
                'notes' => 'Borrador sabatino para cierre de semana.',
            ],
        );

        ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teachers[0]->id,
                'classroom_id' => $classroomB2->id,
                'day_of_week' => 4,
                'starts_at' => '18:00',
                'ends_at' => '20:00',
            ],
            [
                'course_topic_id' => $topicRoutines->id,
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => now()->subDay(),
                'notes' => 'Grupo nocturno A1.',
            ],
        );

        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot1->id, 'student_id' => $students[0]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot1->id, 'student_id' => $students[1]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot1->id, 'student_id' => $students[2]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot1->id, 'student_id' => $students[3]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot2->id, 'student_id' => $students[4]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot2->id, 'student_id' => $students[5]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot2->id, 'student_id' => $students[6]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot4->id, 'student_id' => $students[7]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot4->id, 'student_id' => $students[8]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot4->id, 'student_id' => $students[9]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot5->id, 'student_id' => $students[10]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot5->id, 'student_id' => $students[11]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot6->id, 'student_id' => $students[12]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot3->id, 'student_id' => $students[13]->id],
            ['created_by' => $admin->id],
        );
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
}
