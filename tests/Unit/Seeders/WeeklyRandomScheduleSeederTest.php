<?php

namespace Tests\Unit\Seeders;

use App\Models\Classroom;
use App\Models\ScheduleSlot;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WeeklyRandomScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WeeklyRandomScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-02-10 09:00:00');
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_seeder_creates_at_least_twelve_weekly_courses_with_valid_duration_and_window(): void
    {
        $this->seed(WeeklyRandomScheduleSeeder::class);

        $weeklySlots = ScheduleSlot::query()
            ->where('notes', 'like', 'Seeder semanal aleatorio:%')
            ->get();

        $this->assertGreaterThanOrEqual(12, $weeklySlots->count());
        $this->assertLessThanOrEqual(4, Classroom::query()->where('is_active', true)->count());

        foreach ($weeklySlots as $slot) {
            $startMinutes = $this->toMinutes((string) $slot->starts_at);
            $endMinutes = $this->toMinutes((string) $slot->ends_at);
            $duration = $endMinutes - $startMinutes;

            $this->assertGreaterThanOrEqual(1, $slot->day_of_week);
            $this->assertLessThanOrEqual(6, $slot->day_of_week);
            $this->assertGreaterThanOrEqual(8 * 60, $startMinutes);
            $this->assertLessThanOrEqual(20 * 60, $endMinutes);
            $this->assertGreaterThan(0, $duration);
            $this->assertLessThanOrEqual(180, $duration);
        }
    }

    public function test_seeder_does_not_create_teacher_or_classroom_time_overlaps(): void
    {
        $this->seed(WeeklyRandomScheduleSeeder::class);

        $weeklySlots = ScheduleSlot::query()
            ->where('notes', 'like', 'Seeder semanal aleatorio:%')
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('day_of_week');

        foreach ($weeklySlots as $daySlots) {
            $slots = $daySlots->values();
            $count = $slots->count();

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $slotA = $slots[$i];
                    $slotB = $slots[$j];

                    $overlaps = $this->toMinutes((string) $slotA->starts_at) < $this->toMinutes((string) $slotB->ends_at)
                        && $this->toMinutes((string) $slotA->ends_at) > $this->toMinutes((string) $slotB->starts_at);

                    if (! $overlaps) {
                        continue;
                    }

                    $this->assertFalse(
                        $slotA->classroom_id === $slotB->classroom_id,
                        'Dos cursos no pueden compartir aula en el mismo horario.'
                    );
                    $this->assertFalse(
                        $slotA->teacher_id === $slotB->teacher_id,
                        'Un profesor no puede tener cursos simultaneos.'
                    );
                }
            }
        }
    }

    private function toMinutes(string $timeValue): int
    {
        [$hours, $minutes] = explode(':', substr($timeValue, 0, 5));

        return ((int) $hours * 60) + (int) $minutes;
    }
}

