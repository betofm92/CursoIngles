<?php

namespace Tests\Unit\Controllers\Admin;

use App\Http\Controllers\Admin\ReportController;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\ScheduleSlot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Carbon::setTestNow('2026-02-10 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_index_returns_week_scopes_with_selected_status_filter(): void
    {
        [$closed, $confirmed, $draft] = $this->createSlots();

        $request = Request::create('/admin/reportes', 'GET', [
            'status' => ScheduleSlot::STATUS_CLOSED,
            'week_scope' => 'next',
        ]);

        $view = (new ReportController())->index($request);
        $data = $view->getData();

        $this->assertSame('admin.reports.index', $view->name());
        $this->assertSame(ScheduleSlot::STATUS_CLOSED, $data['statusFilter']);
        $this->assertSame('next', $data['activeScope']);
        $this->assertCount(2, $data['weekScopes']);
        $this->assertSame('Semana actual', $data['weekScopes'][0]['label']);
        $this->assertSame('Semana siguiente', $data['weekScopes'][1]['label']);
        $this->assertEqualsCanonicalizing(
            [$closed->id],
            $data['slotsByScope']['current'][2]->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$closed->id],
            $data['slotsByScope']['next'][2]->pluck('id')->all()
        );
        $this->assertNotContains($confirmed->id, $data['slotsByScope']['current'][3]->pluck('id')->all());
        $this->assertNotContains($draft->id, $data['slotsByScope']['current'][4]->pluck('id')->all());
    }

    public function test_download_csv_returns_expected_attachment(): void
    {
        $this->createSlots();

        $request = Request::create('/admin/reportes/descargar', 'GET', [
            'week_scope' => 'current',
            'status' => 'all',
            'format' => 'csv',
        ]);

        $response = (new ReportController())->download($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', (string) $response->headers->get('Content-Disposition'));
        $content = (string) $response->getContent();
        $this->assertStringContainsString('Curso Reporte Unit', $content);
        $this->assertStringContainsString('Tema Reporte Unit', $content);
    }

    public function test_download_pdf_returns_pdf_attachment(): void
    {
        $this->createSlots();

        $request = Request::create('/admin/reportes/descargar', 'GET', [
            'week_scope' => 'current',
            'status' => ScheduleSlot::STATUS_CLOSED,
            'format' => 'pdf',
        ]);

        $response = (new ReportController())->download($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-1.4', (string) $response->getContent());
    }

    public function test_download_xlsx_returns_binary_file_attachment(): void
    {
        $this->createSlots();

        $request = Request::create('/admin/reportes/descargar', 'GET', [
            'week_scope' => 'next',
            'status' => 'all',
            'format' => 'xlsx',
        ]);

        $response = (new ReportController())->download($request);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);
        $this->assertSame('PK', (string) file_get_contents($filePath, false, null, 0, 2));
    }

    /**
     * @return array{0: ScheduleSlot, 1: ScheduleSlot, 2: ScheduleSlot}
     */
    private function createSlots(): array
    {
        $teacher = User::factory()->create([
            'name' => 'Profesor Reporte Unit',
        ]);
        $teacher->assignRole('profesor');

        $classroom = Classroom::create([
            'code' => 'REPORT-UNIT-A1',
            'name' => 'Aula Reporte Unit',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $course = Course::create([
            'code' => 'REPORT-UNIT-COURSE',
            'name' => 'Curso Reporte Unit',
            'is_active' => true,
        ]);

        $topic = CourseTopic::create([
            'course_id' => $course->id,
            'title' => 'Tema Reporte Unit',
            'is_active' => true,
        ]);

        $closed = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 2,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'status' => ScheduleSlot::STATUS_CLOSED,
            'confirmed_at' => now(),
        ]);

        $confirmed = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 3,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'status' => ScheduleSlot::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $draft = ScheduleSlot::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'course_topic_id' => $topic->id,
            'day_of_week' => 4,
            'starts_at' => '12:00',
            'ends_at' => '14:00',
            'status' => ScheduleSlot::STATUS_DRAFT,
        ]);

        return [$closed, $confirmed, $draft];
    }
}

