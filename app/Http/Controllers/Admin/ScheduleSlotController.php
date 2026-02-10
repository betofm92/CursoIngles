<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
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
            'students' => User::role('estudiante')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
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
