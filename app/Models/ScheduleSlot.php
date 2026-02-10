<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSlot extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CLOSED = 'closed';

    public const DAY_LABELS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miercoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sabado',
        7 => 'Domingo',
    ];

    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'course_topic_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'status',
        'confirmed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public static function dayOptions(): array
    {
        return self::DAY_LABELS;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function courseTopic(): BelongsTo
    {
        return $this->belongsTo(CourseTopic::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'schedule_slot_id', 'student_id')
            ->withTimestamps();
    }

    protected function dayLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::DAY_LABELS[$this->day_of_week] ?? 'No definido',
        );
    }

    protected function capacity(): Attribute
    {
        return Attribute::make(
            get: fn (): int => min((int) ($this->classroom?->capacity ?? 8), 8),
        );
    }

    protected function availableSeats(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $enrolled = $this->enrollments_count ?? $this->enrollments()->count();

                return max($this->capacity - $enrolled, 0);
            },
        );
    }
}
