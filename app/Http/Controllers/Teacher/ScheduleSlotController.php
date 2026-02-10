<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreScheduleSlotRequest;
use App\Http\Requests\Teacher\UpdateScheduleSlotRequest;
use App\Models\Classroom;
use App\Models\CourseTopic;
use App\Models\ScheduleSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleSlotController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();

        return view('teacher.schedule-slots.index', [
            'slots' => $teacher->teachingSlots()
                ->with(['classroom:id,name,capacity', 'courseTopic.course'])
                ->withCount('enrollments')
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
            'classrooms' => Classroom::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'capacity']),
            'topics' => CourseTopic::query()
                ->with('course:id,name')
                ->where('is_active', true)
                ->whereHas('course', fn ($query) => $query->where('is_active', true))
                ->orderBy('title')
                ->get(['id', 'course_id', 'title']),
            'dayOptions' => ScheduleSlot::dayOptions(),
        ]);
    }

    public function store(StoreScheduleSlotRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->ensureActiveReferences($validated['classroom_id'], $validated['course_topic_id']);
        $this->ensureNoOverlap(
            teacherId: $request->user()->id,
            classroomId: $validated['classroom_id'],
            dayOfWeek: $validated['day_of_week'],
            startsAt: $validated['starts_at'],
            endsAt: $validated['ends_at'],
        );

        $request->user()->teachingSlots()->create([
            ...$validated,
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        return back()->with('success', 'Horario creado en estado borrador.');
    }

    public function update(UpdateScheduleSlotRequest $request, ScheduleSlot $scheduleSlot): RedirectResponse
    {
        $this->ensureDraftOwnedByTeacher($request, $scheduleSlot);

        $validated = $request->validated();
        $this->ensureActiveReferences($validated['classroom_id'], $validated['course_topic_id']);
        $this->ensureNoOverlap(
            teacherId: $request->user()->id,
            classroomId: $validated['classroom_id'],
            dayOfWeek: $validated['day_of_week'],
            startsAt: $validated['starts_at'],
            endsAt: $validated['ends_at'],
            ignoreSlotId: $scheduleSlot->id,
        );

        $scheduleSlot->update($validated);

        return back()->with('success', 'Borrador actualizado.');
    }

    public function destroy(Request $request, ScheduleSlot $scheduleSlot): RedirectResponse
    {
        $this->ensureDraftOwnedByTeacher($request, $scheduleSlot);

        $scheduleSlot->delete();

        return back()->with('success', 'Horario borrador eliminado.');
    }

    public function confirm(Request $request, ScheduleSlot $scheduleSlot): RedirectResponse
    {
        if ($scheduleSlot->teacher_id !== $request->user()->id) {
            abort(403);
        }

        if ($scheduleSlot->status !== ScheduleSlot::STATUS_DRAFT) {
            return back()->with('error', 'Solo se pueden confirmar horarios en borrador.');
        }

        $this->ensureNoOverlap(
            teacherId: $request->user()->id,
            classroomId: $scheduleSlot->classroom_id,
            dayOfWeek: $scheduleSlot->day_of_week,
            startsAt: $scheduleSlot->starts_at,
            endsAt: $scheduleSlot->ends_at,
            ignoreSlotId: $scheduleSlot->id,
        );

        $scheduleSlot->update([
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Horario confirmado. Ahora Admin puede asignar estudiantes.');
    }

    private function ensureDraftOwnedByTeacher(Request $request, ScheduleSlot $scheduleSlot): void
    {
        if ($scheduleSlot->teacher_id !== $request->user()->id) {
            abort(403);
        }

        if ($scheduleSlot->status !== ScheduleSlot::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'slot' => 'Solo se pueden editar o eliminar horarios en borrador.',
            ]);
        }
    }

    private function ensureActiveReferences(int $classroomId, int $topicId): void
    {
        if (! Classroom::whereKey($classroomId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'classroom_id' => 'El aula seleccionada no esta disponible.',
            ]);
        }

        if (! CourseTopic::whereKey($topicId)->where('is_active', true)->whereHas('course', fn ($query) => $query->where('is_active', true))->exists()) {
            throw ValidationException::withMessages([
                'course_topic_id' => 'El tema de curso seleccionado no esta disponible.',
            ]);
        }
    }

    private function ensureNoOverlap(
        int $teacherId,
        int $classroomId,
        int $dayOfWeek,
        string $startsAt,
        string $endsAt,
        ?int $ignoreSlotId = null
    ): void {
        $baseConflictQuery = ScheduleSlot::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('status', '!=', ScheduleSlot::STATUS_CLOSED)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($ignoreSlotId !== null) {
            $baseConflictQuery->whereKeyNot($ignoreSlotId);
        }

        if ((clone $baseConflictQuery)->where('teacher_id', $teacherId)->exists()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Ya tienes un horario que se cruza en ese rango.',
            ]);
        }

        if ((clone $baseConflictQuery)->where('classroom_id', $classroomId)->exists()) {
            throw ValidationException::withMessages([
                'classroom_id' => 'El aula ya esta ocupada en ese rango.',
            ]);
        }
    }
}
