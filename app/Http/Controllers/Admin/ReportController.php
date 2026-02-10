<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSlot;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', ScheduleSlot::STATUS_CLOSED);
        if (! array_key_exists($statusFilter, $this->statusOptions())) {
            $statusFilter = ScheduleSlot::STATUS_CLOSED;
        }

        $activeScope = $request->query('week_scope', 'current');
        if (! in_array($activeScope, ['current', 'next'], true)) {
            $activeScope = 'current';
        }

        $dayOptions = ScheduleSlot::dayOptions();
        $weekScopes = $this->buildWeekScopes($dayOptions);
        $slots = $this->baseReportSlotsQuery($statusFilter)->get();

        $slotsByScope = [];
        foreach ($weekScopes as $scope) {
            $slotsByScope[$scope['key']] = $this->slotsByDay($slots, array_keys($dayOptions));
        }

        return view('admin.reports.index', [
            'statusFilter' => $statusFilter,
            'statusOptions' => $this->statusOptions(),
            'activeScope' => $activeScope,
            'weekScopes' => $weekScopes,
            'slotsByScope' => $slotsByScope,
        ]);
    }

    public function download(Request $request): Response|BinaryFileResponse
    {
        $validated = $request->validate([
            'week_scope' => ['required', 'string', 'in:current,next'],
            'status' => ['required', 'string', 'in:all,draft,confirmed,closed'],
            'format' => ['required', 'string', 'in:csv,xlsx,pdf'],
        ]);

        $dayOptions = ScheduleSlot::dayOptions();
        $weekScopes = $this->buildWeekScopes($dayOptions);
        $scope = collect($weekScopes)->firstWhere('key', $validated['week_scope']);
        if (! $scope) {
            abort(422);
        }

        $slots = $this->baseReportSlotsQuery($validated['status'])->get();
        $rows = $this->buildRows($slots, $scope['start']);
        $headers = ['Dia', 'Fecha', 'Hora inicio', 'Hora fin', 'Estado', 'Curso', 'Tema', 'Profesor', 'Aula', 'Inscritos'];

        $fileSuffix = sprintf(
            '%s_%s_%s',
            $validated['week_scope'],
            $validated['status'],
            Carbon::now()->format('Ymd_His')
        );

        return match ($validated['format']) {
            'csv' => $this->downloadCsv($headers, $rows, "reporte_horarios_{$fileSuffix}.csv"),
            'xlsx' => $this->downloadXlsx($headers, $rows, "reporte_horarios_{$fileSuffix}.xlsx"),
            'pdf' => $this->downloadPdf($headers, $rows, "reporte_horarios_{$fileSuffix}.pdf", $scope, $validated['status']),
        };
    }

    private function statusOptions(): array
    {
        return [
            'all' => 'Todos',
            ScheduleSlot::STATUS_DRAFT => 'Borrador',
            ScheduleSlot::STATUS_CONFIRMED => 'Confirmado',
            ScheduleSlot::STATUS_CLOSED => 'Cerrado',
        ];
    }

    private function baseReportSlotsQuery(string $statusFilter)
    {
        $query = ScheduleSlot::query()
            ->with([
                'teacher:id,name',
                'classroom:id,name',
                'courseTopic:id,course_id,title',
                'courseTopic.course:id,name',
            ])
            ->withCount('enrollments')
            ->orderBy('day_of_week')
            ->orderBy('starts_at');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        return $query;
    }

    /**
     * @param array<int, string> $dayOptions
     * @return array<int, array<string, mixed>>
     */
    private function buildWeekScopes(array $dayOptions): array
    {
        $today = Carbon::now();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $nextWeekStart = $weekStart->copy()->addWeek();

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

        return [
            [
                'key' => 'current',
                'label' => 'Semana actual',
                'start' => $weekStart,
                'end' => $weekStart->copy()->addDays(5),
                'range' => sprintf('%s - %s', $weekStart->format('d/m'), $weekStart->copy()->addDays(5)->format('d/m')),
                'days' => $buildDays($weekStart),
            ],
            [
                'key' => 'next',
                'label' => 'Semana siguiente',
                'start' => $nextWeekStart,
                'end' => $nextWeekStart->copy()->addDays(5),
                'range' => sprintf('%s - %s', $nextWeekStart->format('d/m'), $nextWeekStart->copy()->addDays(5)->format('d/m')),
                'days' => $buildDays($nextWeekStart),
            ],
        ];
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

    /**
     * @param EloquentCollection<int, ScheduleSlot> $slots
     * @return array<int, array<string, scalar>>
     */
    private function buildRows(EloquentCollection $slots, Carbon $weekStart): array
    {
        $rows = [];

        foreach ($slots as $slot) {
            $date = $weekStart->copy()->addDays(((int) $slot->day_of_week) - 1)->format('Y-m-d');
            $rows[] = [
                'Dia' => $slot->day_label,
                'Fecha' => $date,
                'Hora inicio' => substr((string) $slot->starts_at, 0, 5),
                'Hora fin' => substr((string) $slot->ends_at, 0, 5),
                'Estado' => $this->statusOptions()[$slot->status] ?? (string) $slot->status,
                'Curso' => (string) ($slot->courseTopic?->course?->name ?? 'No definido'),
                'Tema' => (string) ($slot->courseTopic?->title ?? 'No definido'),
                'Profesor' => (string) ($slot->teacher?->name ?? 'No definido'),
                'Aula' => (string) ($slot->classroom?->name ?? 'No definida'),
                'Inscritos' => (int) ($slot->enrollments_count ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<string, scalar>> $rows
     */
    private function downloadCsv(array $headers, array $rows, string $fileName): Response
    {
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response("\xEF\xBB\xBF".$content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<string, scalar>> $rows
     */
    private function downloadXlsx(array $headers, array $rows, string $fileName): BinaryFileResponse
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'rpt_xlsx_');
        if ($tmpPath === false) {
            abort(500, 'No se pudo crear archivo temporal para XLSX.');
        }
        $xlsxPath = $tmpPath.'.xlsx';
        @unlink($tmpPath);

        $zip = new ZipArchive();
        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo generar el archivo XLSX.');
        }

        $sheetRows = [$headers];
        foreach ($rows as $row) {
            $sheetRows[] = array_map(fn ($value): string => (string) $value, array_values($row));
        }

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheetXml($sheetRows));
        $zip->close();

        return response()->download(
            $xlsxPath,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<string, scalar>> $rows
     * @param array<string, mixed> $scope
     */
    private function downloadPdf(array $headers, array $rows, string $fileName, array $scope, string $statusFilter): Response
    {
        $lines = [
            'Reporte semanal de horarios',
            sprintf('Rango: %s', $scope['range']),
            sprintf('Estado filtrado: %s', $this->statusOptions()[$statusFilter] ?? $statusFilter),
            '',
            implode(' | ', $headers),
        ];

        foreach ($rows as $row) {
            $line = implode(' | ', array_map(fn ($value): string => (string) $value, array_values($row)));
            $lines[] = Str::limit($line, 150, '...');
        }

        if (count($rows) === 0) {
            $lines[] = 'No hay registros para los filtros seleccionados.';
        }

        $pdf = $this->buildSimplePdf($lines);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * @param array<int, string> $lines
     */
    private function buildSimplePdf(array $lines): string
    {
        $normalizedLines = collect($lines)
            ->map(fn (string $line): string => Str::ascii($line))
            ->values()
            ->all();

        $content = "BT\n/F1 10 Tf\n40 800 Td\n12 TL\n";
        foreach ($normalizedLines as $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "({$escaped}) Tj\nT*\n";
        }
        $content .= "ET";

        $objects = [];
        $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objects[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
        $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
        $objects[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
        $objects[] = "5 0 obj << /Length ".strlen($content)." >> stream\n{$content}\nendstream endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function xlsxContentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function xlsxRootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function xlsxWorkbookXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Reporte" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML;
    }

    private function xlsxWorkbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function xlsxStylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1">
        <font>
            <sz val="11"/>
            <name val="Calibri"/>
        </font>
    </fonts>
    <fills count="1">
        <fill>
            <patternFill patternType="none"/>
        </fill>
    </fills>
    <borders count="1">
        <border/>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    </cellXfs>
</styleSheet>
XML;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function xlsxSheetXml(array $rows): string
    {
        $xmlRows = [];

        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $xmlCells = [];

            foreach ($cells as $colIndex => $value) {
                $cellRef = $this->xlsxColumnName($colIndex + 1).$rowNumber;
                $escapedValue = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $xmlCells[] = sprintf(
                    '<c r="%s" t="inlineStr"><is><t>%s</t></is></c>',
                    $cellRef,
                    $escapedValue
                );
            }

            $xmlRows[] = sprintf('<row r="%d">%s</row>', $rowNumber, implode('', $xmlCells));
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>%s</sheetData>'
            .'</worksheet>',
            implode('', $xmlRows)
        );
    }

    private function xlsxColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}

