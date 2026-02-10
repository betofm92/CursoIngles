<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
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

        $teacherA = User::firstOrCreate(
            ['email' => 'profesor1@cursoingles.test'],
            [
                'name' => 'Prof. Maria Torres',
                'password' => Hash::make('password'),
            ],
        );
        $teacherA->syncRoles(['profesor']);

        $teacherB = User::firstOrCreate(
            ['email' => 'profesor2@cursoingles.test'],
            [
                'name' => 'Prof. Diego Ruiz',
                'password' => Hash::make('password'),
            ],
        );
        $teacherB->syncRoles(['profesor']);

        $students = collect([
            ['name' => 'Ana Perez', 'email' => 'estudiante1@cursoingles.test'],
            ['name' => 'Luis Gomez', 'email' => 'estudiante2@cursoingles.test'],
            ['name' => 'Sofia Rojas', 'email' => 'estudiante3@cursoingles.test'],
            ['name' => 'Mateo Diaz', 'email' => 'estudiante4@cursoingles.test'],
            ['name' => 'Camila Vega', 'email' => 'estudiante5@cursoingles.test'],
        ])->map(function (array $studentData) {
            $student = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password'),
                ],
            );
            $student->syncRoles(['estudiante']);

            return $student;
        });

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

        $slot1 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teacherA->id,
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

        ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teacherA->id,
                'classroom_id' => $classroomA2->id,
                'day_of_week' => 3,
                'starts_at' => '14:00',
                'ends_at' => '16:00',
            ],
            [
                'course_topic_id' => $topicRoutines->id,
                'status' => ScheduleSlot::STATUS_DRAFT,
                'confirmed_at' => null,
                'notes' => 'Pendiente de confirmacion del profesor.',
            ],
        );

        $slot3 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teacherB->id,
                'classroom_id' => $classroomB1->id,
                'day_of_week' => 2,
                'starts_at' => '18:00',
                'ends_at' => '20:00',
            ],
            [
                'course_topic_id' => $topicPast->id,
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => now()->subDays(3),
                'notes' => 'Grupo nocturno para adultos.',
            ],
        );

        $slot4 = ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teacherB->id,
                'classroom_id' => $classroomA1->id,
                'day_of_week' => 5,
                'starts_at' => '09:00',
                'ends_at' => '11:00',
            ],
            [
                'course_topic_id' => $topicWork->id,
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => now()->subDays(1),
                'notes' => 'Enfocado en speaking laboral.',
            ],
        );

        ScheduleSlot::updateOrCreate(
            [
                'teacher_id' => $teacherA->id,
                'classroom_id' => $classroomB1->id,
                'day_of_week' => 6,
                'starts_at' => '09:00',
                'ends_at' => '11:00',
            ],
            [
                'course_topic_id' => $topicPast->id,
                'status' => ScheduleSlot::STATUS_CLOSED,
                'confirmed_at' => now()->subDays(10),
                'notes' => 'Cohorte finalizada.',
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
            ['schedule_slot_id' => $slot3->id, 'student_id' => $students[2]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot3->id, 'student_id' => $students[3]->id],
            ['created_by' => $admin->id],
        );
        Enrollment::firstOrCreate(
            ['schedule_slot_id' => $slot4->id, 'student_id' => $students[0]->id],
            ['created_by' => $admin->id],
        );
    }
}
