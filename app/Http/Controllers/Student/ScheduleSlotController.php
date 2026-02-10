<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleSlotController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.schedule-slots.index', [
            'slots' => ScheduleSlot::query()
                ->with(['teacher:id,name', 'classroom:id,name,capacity', 'courseTopic.course'])
                ->withCount('enrollments')
                ->whereIn('status', [ScheduleSlot::STATUS_CONFIRMED, ScheduleSlot::STATUS_CLOSED])
                ->whereHas('enrollments', fn ($query) => $query->where('student_id', $request->user()->id))
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
        ]);
    }
}
