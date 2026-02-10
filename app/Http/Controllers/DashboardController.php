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
        $todayDayOfWeek = $today->dayOfWeekIso;
        $todayDayLabel = ScheduleSlot::dayOptions()[$todayDayOfWeek] ?? 'Domingo';
        $todayDateLabel = $today->translatedFormat('d/m/Y');

        if ($user->hasRole('admin')) {
            return view('dashboard', [
                'role' => 'admin',
                'stats' => [
                    'professors' => User::role('profesor')->count(),
                    'students' => User::role('estudiante')->count(),
                    'confirmed_slots' => ScheduleSlot::where('status', ScheduleSlot::STATUS_CONFIRMED)->count(),
                    'pending_slots' => ScheduleSlot::where('status', ScheduleSlot::STATUS_DRAFT)->count(),
                ],
                'slots' => ScheduleSlot::query()
                    ->with(['teacher:id,name', 'classroom:id,name,capacity', 'courseTopic.course'])
                    ->withCount('enrollments')
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at')
                    ->limit(6)
                    ->get(),
                'todayDayLabel' => $todayDayLabel,
                'todayDateLabel' => $todayDateLabel,
                'todayCourses' => ScheduleSlot::query()
                    ->with(['teacher:id,name', 'courseTopic.course'])
                    ->withCount('enrollments')
                    ->where('day_of_week', $todayDayOfWeek)
                    ->whereIn('status', [ScheduleSlot::STATUS_DRAFT, ScheduleSlot::STATUS_CONFIRMED])
                    ->orderBy('starts_at')
                    ->get(),
            ]);
        }

        if ($user->hasRole('profesor')) {
            return view('dashboard', [
                'role' => 'profesor',
                'stats' => [
                    'total_slots' => $user->teachingSlots()->count(),
                    'draft_slots' => $user->teachingSlots()->where('status', ScheduleSlot::STATUS_DRAFT)->count(),
                    'confirmed_slots' => $user->teachingSlots()->where('status', ScheduleSlot::STATUS_CONFIRMED)->count(),
                ],
                'slots' => $user->teachingSlots()
                    ->with(['classroom:id,name,capacity', 'courseTopic.course'])
                    ->withCount('enrollments')
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at')
                    ->limit(6)
                    ->get(),
                'todayDayLabel' => $todayDayLabel,
                'todayDateLabel' => $todayDateLabel,
                'todayCourses' => $user->teachingSlots()
                    ->with(['teacher:id,name', 'courseTopic.course'])
                    ->withCount('enrollments')
                    ->where('day_of_week', $todayDayOfWeek)
                    ->whereIn('status', [ScheduleSlot::STATUS_DRAFT, ScheduleSlot::STATUS_CONFIRMED])
                    ->orderBy('starts_at')
                    ->get(),
            ]);
        }

        return view('dashboard', [
            'role' => 'estudiante',
            'stats' => [
                'assigned_slots' => ScheduleSlot::query()
                    ->where('status', ScheduleSlot::STATUS_CONFIRMED)
                    ->whereHas('enrollments', fn ($query) => $query->where('student_id', $user->id))
                    ->count(),
            ],
            'slots' => ScheduleSlot::query()
                ->with(['teacher:id,name', 'classroom:id,name,capacity', 'courseTopic.course'])
                ->withCount('enrollments')
                ->where('status', ScheduleSlot::STATUS_CONFIRMED)
                ->whereHas('enrollments', fn ($query) => $query->where('student_id', $user->id))
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->limit(6)
                ->get(),
            'todayDayLabel' => $todayDayLabel,
            'todayDateLabel' => $todayDateLabel,
            'todayCourses' => ScheduleSlot::query()
                ->with(['teacher:id,name', 'courseTopic.course'])
                ->withCount('enrollments')
                ->where('status', ScheduleSlot::STATUS_CONFIRMED)
                ->where('day_of_week', $todayDayOfWeek)
                ->whereHas('enrollments', fn ($query) => $query->where('student_id', $user->id))
                ->orderBy('starts_at')
                ->get(),
        ]);
    }
}
