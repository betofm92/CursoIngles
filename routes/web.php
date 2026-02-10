<?php

use App\Http\Controllers\Admin\ScheduleSlotController as AdminScheduleSlotController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\ScheduleSlotController as StudentScheduleSlotController;
use App\Http\Controllers\Teacher\ScheduleSlotController as TeacherScheduleSlotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:profesor')->prefix('profesor')->name('teacher.')->group(function () {
        Route::get('/horarios', [TeacherScheduleSlotController::class, 'index'])->name('schedule-slots.index');
        Route::post('/horarios', [TeacherScheduleSlotController::class, 'store'])->name('schedule-slots.store');
        Route::put('/horarios/{scheduleSlot}', [TeacherScheduleSlotController::class, 'update'])->name('schedule-slots.update');
        Route::delete('/horarios/{scheduleSlot}', [TeacherScheduleSlotController::class, 'destroy'])->name('schedule-slots.destroy');
        Route::patch('/horarios/{scheduleSlot}/confirmar', [TeacherScheduleSlotController::class, 'confirm'])->name('schedule-slots.confirm');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/horarios', [AdminScheduleSlotController::class, 'index'])->name('schedule-slots.index');
        Route::post('/horarios/{scheduleSlot}/enrollments', [AdminScheduleSlotController::class, 'storeEnrollment'])->name('schedule-slots.enrollments.store');
        Route::delete('/horarios/{scheduleSlot}/enrollments/{enrollment}', [AdminScheduleSlotController::class, 'destroyEnrollment'])->name('schedule-slots.enrollments.destroy');
        Route::patch('/horarios/{scheduleSlot}/close', [AdminScheduleSlotController::class, 'close'])->name('schedule-slots.close');

        Route::get('/usuarios', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/usuarios/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('role:estudiante')->prefix('estudiante')->name('student.')->group(function () {
        Route::get('/horarios', [StudentScheduleSlotController::class, 'index'])->name('schedule-slots.index');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
