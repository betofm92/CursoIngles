<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleSlotController extends Controller
{
    public function index(): View
    {
        $slots = ScheduleSlot::query()
            ->with([
                'teacher:id,name',
                'classroom:id,name,capacity',
                'courseTopic.course',
                'enrollments.student:id,name,email',
            ])
            ->withCount('enrollments')
            ->whereIn('status', [ScheduleSlot::STATUS_CONFIRMED, ScheduleSlot::STATUS_CLOSED])
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        $dayOptions = ScheduleSlot::dayOptions();
        $today = Carbon::now();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $nextWeekStart = $weekStart->copy()->addWeek();
        $activeDay = $today->dayOfWeekIso > 6 ? 1 : $today->dayOfWeekIso;

        $buildDays = function (Carbon $start) use ($dayOptions): array {
            return collect($dayOptions)
                ->map(fn (string $label, int $day): array => [
                    'day_of_week' => $day,
                    'label' => $label,
                    'date' => $start->copy()->addDays($day - 1)->translatedFormat('d/m'),
                ])
                ->values()
                ->all();
        };

        $weekScopes = [
            [
                'key' => 'current',
                'label' => 'Semana actual',
                'range' => sprintf('%s - %s', $weekStart->format('d/m'), $weekStart->copy()->addDays(5)->format('d/m')),
                'days' => $buildDays($weekStart),
            ],
            [
                'key' => 'next',
                'label' => 'Semana siguiente',
                'range' => sprintf('%s - %s', $nextWeekStart->format('d/m'), $nextWeekStart->copy()->addDays(5)->format('d/m')),
                'days' => $buildDays($nextWeekStart),
            ],
        ];

        $slotsByDay = $this->slotsByDay($slots, array_keys($dayOptions));
        $slotsByScope = [
            'current' => $slotsByDay,
            'next' => $slotsByDay,
        ];

        return view('admin.schedule-slots.index', [
            'slots' => $slots,
            'weekScopes' => $weekScopes,
            'activeDay' => $activeDay,
            'slotsByScope' => $slotsByScope,
            'dayOptions' => $dayOptions,
            'teachers' => User::role('profesor')->orderBy('name')->get(['id', 'name', 'email']),
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'capacity']),
            'students' => User::role('estudiante')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function storeCourse(StoreCourseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $teacher = User::role('profesor')->whereKey($validated['teacher_id'])->first();
        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacher_id' => 'El usuario seleccionado no tiene rol de profesor.',
            ]);
        }

        $classroom = Classroom::query()
            ->whereKey($validated['classroom_id'])
            ->where('is_active', true)
            ->first();
        if (! $classroom) {
            throw ValidationException::withMessages([
                'classroom_id' => 'El aula seleccionada no esta disponible.',
            ]);
        }

        $studentIds = collect($validated['student_ids'] ?? [])
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        $validStudentCount = User::role('estudiante')->whereIn('id', $studentIds)->count();
        if ($validStudentCount !== $studentIds->count()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Uno o mas usuarios seleccionados no tienen rol de estudiante.',
            ]);
        }

        $capacity = min((int) $classroom->capacity, 8);
        if ($studentIds->count() > $capacity) {
            throw ValidationException::withMessages([
                'student_ids' => 'No puedes asignar mas estudiantes que la capacidad del aula.',
            ]);
        }

        $baseConflictQuery = ScheduleSlot::query()
            ->where('day_of_week', $validated['day_of_week'])
            ->where('status', '!=', ScheduleSlot::STATUS_CLOSED)
            ->where('starts_at', '<', $validated['ends_at'])
            ->where('ends_at', '>', $validated['starts_at']);

        if ((clone $baseConflictQuery)->where('teacher_id', $teacher->id)->exists()) {
            throw ValidationException::withMessages([
                'starts_at' => 'El profesor ya tiene un horario que se cruza en ese rango.',
            ]);
        }

        if ((clone $baseConflictQuery)->where('classroom_id', $classroom->id)->exists()) {
            throw ValidationException::withMessages([
                'classroom_id' => 'El aula ya esta ocupada en ese rango.',
            ]);
        }

        if ($studentIds->isNotEmpty()) {
            $studentsWithOverlap = ScheduleSlot::query()
                ->where('status', ScheduleSlot::STATUS_CONFIRMED)
                ->where('day_of_week', $validated['day_of_week'])
                ->where('starts_at', '<', $validated['ends_at'])
                ->where('ends_at', '>', $validated['starts_at'])
                ->whereHas('enrollments', fn ($query) => $query->whereIn('student_id', $studentIds))
                ->exists();

            if ($studentsWithOverlap) {
                throw ValidationException::withMessages([
                    'student_ids' => 'Uno o mas estudiantes ya tienen una clase en ese rango horario.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $teacher, $classroom, $studentIds, $request): void {
            $course = Course::create([
                'code' => Str::upper($validated['course_code']),
                'name' => $validated['course_name'],
                'description' => $validated['course_description'] ?? null,
                'is_active' => true,
            ]);

            $topic = CourseTopic::create([
                'course_id' => $course->id,
                'title' => $validated['topic_title'],
                'description' => $validated['topic_description'] ?? null,
                'is_active' => true,
            ]);

            $slot = ScheduleSlot::create([
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'course_topic_id' => $topic->id,
                'day_of_week' => $validated['day_of_week'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'status' => ScheduleSlot::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($studentIds as $studentId) {
                Enrollment::create([
                    'schedule_slot_id' => $slot->id,
                    'student_id' => $studentId,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('success', 'Curso creado y horario confirmado correctamente.');
    }

    public function storeEnrollment(StoreEnrollmentRequest $request, ScheduleSlot $scheduleSlot): RedirectResponse
    {
        if ($scheduleSlot->status !== ScheduleSlot::STATUS_CONFIRMED) {
            return back()->with('error', 'Solo puedes asignar estudiantes a horarios confirmados.');
        }

        $student = User::role('estudiante')->whereKey($request->integer('student_id'))->first();
        if (! $student) {
            return back()->with('error', 'El usuario seleccionado no tiene rol de estudiante.');
        }

        if ($scheduleSlot->enrollments()->where('student_id', $student->id)->exists()) {
            return back()->with('error', 'El estudiante ya esta asignado a este horario.');
        }

        $scheduleSlot->loadMissing('classroom');
        $capacity = min((int) $scheduleSlot->classroom->capacity, 8);
        $enrolledCount = $scheduleSlot->enrollments()->count();

        if ($enrolledCount >= $capacity) {
            return back()->with('error', 'Este horario ya alcanzo su capacidad maxima.');
        }

        $studentHasOverlap = ScheduleSlot::query()
            ->where('status', ScheduleSlot::STATUS_CONFIRMED)
            ->where('day_of_week', $scheduleSlot->day_of_week)
            ->where('starts_at', '<', $scheduleSlot->ends_at)
            ->where('ends_at', '>', $scheduleSlot->starts_at)
            ->whereHas('enrollments', fn ($query) => $query->where('student_id', $student->id))
            ->whereKeyNot($scheduleSlot->id)
            ->exists();

        if ($studentHasOverlap) {
            return back()->with('error', 'El estudiante ya tiene otra clase en ese rango horario.');
        }

        Enrollment::create([
            'schedule_slot_id' => $scheduleSlot->id,
            'student_id' => $student->id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Estudiante asignado correctamente.');
    }

    public function destroyEnrollment(ScheduleSlot $scheduleSlot, Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->schedule_slot_id !== $scheduleSlot->id) {
            abort(404);
        }

        $enrollment->delete();

        return back()->with('success', 'Asignacion eliminada.');
    }

    public function close(ScheduleSlot $scheduleSlot): RedirectResponse
    {
        if ($scheduleSlot->status !== ScheduleSlot::STATUS_CONFIRMED) {
            return back()->with('error', 'Solo se pueden cerrar horarios confirmados.');
        }

        $scheduleSlot->update(['status' => ScheduleSlot::STATUS_CLOSED]);

        return back()->with('success', 'Horario cerrado.');
    }

    /**
     * @param EloquentCollection<int, ScheduleSlot> $slots
     * @param list<int> $days
     * @return array<int, EloquentCollection<int, ScheduleSlot>>
     */
    private function slotsByDay(EloquentCollection $slots, array $days): array
    {
        $byDay = [];

        foreach ($days as $day) {
            /** @var EloquentCollection<int, ScheduleSlot> $daySlots */
            $daySlots = $slots->where('day_of_week', $day)->values();
            $byDay[$day] = $daySlots;
        }

        return $byDay;
    }
}
