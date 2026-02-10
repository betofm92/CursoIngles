<?php

namespace App\Http\Controllers;

use App\Models\ScheduleSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::now();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $dayOptions = ScheduleSlot::dayOptions();
        $activeWeekDay = $today->dayOfWeekIso > 6 ? 1 : $today->dayOfWeekIso;
        $weekDays = collect($dayOptions)
            ->map(fn (string $label, int $day): array => [
                'day_of_week' => $day,
                'label' => $label,
                'date' => $weekStart->copy()->addDays($day - 1)->translatedFormat('d/m'),
            ])
            ->values()
            ->all();

        if ($user->hasRole('admin')) {
            $weeklyQuery = ScheduleSlot::query()
                ->with(['teacher:id,name', 'classroom:id,name,capacity', 'courseTopic.course'])
                ->withCount('enrollments')
                ->whereIn('status', [ScheduleSlot::STATUS_DRAFT, ScheduleSlot::STATUS_CONFIRMED]);

            return view('dashboard', [
                'role' => 'admin',
                'stats' => [
                    'professors' => User::role('profesor')->count(),
                    'students' => User::role('estudiante')->count(),
                    'confirmed_slots' => ScheduleSlot::where('status', ScheduleSlot::STATUS_CONFIRMED)->count(),
                    'pending_slots' => ScheduleSlot::where('status', ScheduleSlot::STATUS_DRAFT)->count(),
                ],
                'slots' => (clone $weeklyQuery)
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at')
                    ->limit(6)
                    ->get(),
                'weekDays' => $weekDays,
                'activeWeekDay' => $activeWeekDay,
                'weekCoursesByDay' => $this->coursesByDay($weeklyQuery, array_keys($dayOptions)),
            ]);
        }

        if ($user->hasRole('profesor')) {
            $weeklyQuery = $user->teachingSlots()
                ->with(['teacher:id,name', 'classroom:id,name,capacity', 'courseTopic.course'])
                ->withCount('enrollments')
                ->whereIn('status', [ScheduleSlot::STATUS_DRAFT, ScheduleSlot::STATUS_CONFIRMED]);

            return view('dashboard', [
                'role' => 'profesor',
                'stats' => [
                    'total_slots' => $user->teachingSlots()->count(),
                    'draft_slots' => $user->teachingSlots()->where('status', ScheduleSlot::STATUS_DRAFT)->count(),
                    'confirmed_slots' => $user->teachingSlots()->where('status', ScheduleSlot::STATUS_CONFIRMED)->count(),
                ],
                'slots' => (clone $weeklyQuery)
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at')
                    ->limit(6)
                    ->get(),
                'weekDays' => $weekDays,
                'activeWeekDay' => $activeWeekDay,
                'weekCoursesByDay' => $this->coursesByDay($weeklyQuery, array_keys($dayOptions)),
            ]);
        }

        $weeklyQuery = ScheduleSlot::query()
            ->with(['teacher:id,name', 'classroom:id,name,capacity', 'courseTopic.course'])
            ->withCount('enrollments')
            ->where('status', ScheduleSlot::STATUS_CONFIRMED)
            ->whereHas('enrollments', fn ($query) => $query->where('student_id', $user->id));

        return view('dashboard', [
            'role' => 'estudiante',
            'stats' => [
                'assigned_slots' => ScheduleSlot::query()
                    ->where('status', ScheduleSlot::STATUS_CONFIRMED)
                    ->whereHas('enrollments', fn ($query) => $query->where('student_id', $user->id))
                    ->count(),
            ],
            'slots' => (clone $weeklyQuery)
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->limit(6)
                ->get(),
            'weekDays' => $weekDays,
            'activeWeekDay' => $activeWeekDay,
            'weekCoursesByDay' => $this->coursesByDay($weeklyQuery, array_keys($dayOptions)),
        ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $baseQuery
     * @param list<int> $days
     * @return array<int, \Illuminate\Database\Eloquent\Collection<int, ScheduleSlot>>
     */
    private function coursesByDay($baseQuery, array $days): array
    {
        $byDay = [];

        foreach ($days as $day) {
            $byDay[$day] = (clone $baseQuery)
                ->where('day_of_week', $day)
                ->orderBy('starts_at')
                ->get();
        }

        return $byDay;
    }
}
