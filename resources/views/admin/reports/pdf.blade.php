<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte semanal</title>
    <style>
        @page {
            margin: 28px 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f172a;
            background: #f8fafc;
        }

        .header {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px;
            background: #ffffff;
        }

        .title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .subtitle {
            margin: 6px 0 0;
            font-size: 12px;
            color: #334155;
        }

        .meta {
            margin-top: 12px;
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 0;
            vertical-align: top;
        }

        .meta-chip {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            font-size: 10px;
            font-weight: 700;
            padding: 5px 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .summary {
            margin-top: 12px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .summary td {
            width: 25%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
        }

        .summary-label {
            margin: 0;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 700;
        }

        .summary-value {
            margin: 4px 0 0;
            font-size: 20px;
            color: #0f172a;
            font-weight: 800;
            line-height: 1.1;
        }

        .table-wrap {
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        table.report thead th {
            background: #0f172a;
            color: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 9px;
            font-weight: 700;
            text-align: left;
            padding: 8px 7px;
        }

        table.report tbody td {
            border-top: 1px solid #e2e8f0;
            padding: 7px;
            vertical-align: top;
            color: #1e293b;
        }

        table.report tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .status-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border: 1px solid transparent;
        }

        .status-confirmado {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        .status-cerrado {
            background: #e2e8f0;
            border-color: #cbd5e1;
            color: #334155;
        }

        .status-borrador {
            background: #fef3c7;
            border-color: #fcd34d;
            color: #92400e;
        }

        .course-name {
            font-weight: 700;
            color: #0f172a;
        }

        .muted {
            color: #64748b;
        }

        .empty {
            padding: 28px 14px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <section class="header">
        <h1 class="title">Reporte semanal de horarios</h1>
        <p class="subtitle">Instituto de Idiomas - Gestion academica</p>

        <table class="meta">
            <tr>
                <td>
                    <span class="meta-chip">Rango {{ $scope['range'] }}</span>
                    <span class="meta-chip">Estado {{ $statusLabel }}</span>
                    <span class="meta-chip">Generado {{ $generatedAt }}</span>
                </td>
            </tr>
        </table>

        <table class="summary">
            <tr>
                <td>
                    <p class="summary-label">Cursos</p>
                    <p class="summary-value">{{ $summary['total_courses'] }}</p>
                </td>
                <td>
                    <p class="summary-label">Inscripciones</p>
                    <p class="summary-value">{{ $summary['total_enrolled'] }}</p>
                </td>
                <td>
                    <p class="summary-label">Profesores</p>
                    <p class="summary-value">{{ $summary['unique_teachers'] }}</p>
                </td>
                <td>
                    <p class="summary-label">Aulas</p>
                    <p class="summary-value">{{ $summary['unique_classrooms'] }}</p>
                </td>
            </tr>
        </table>
    </section>

    <section class="table-wrap">
        <table class="report">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $statusClass = match (strtolower((string) $row['Estado'])) {
                            'confirmado' => 'status-confirmado',
                            'cerrado' => 'status-cerrado',
                            default => 'status-borrador',
                        };
                    @endphp
                    <tr>
                        <td>{{ $row['Dia'] }}</td>
                        <td class="muted">{{ $row['Fecha'] }}</td>
                        <td>{{ $row['Hora inicio'] }}</td>
                        <td>{{ $row['Hora fin'] }}</td>
                        <td><span class="status-pill {{ $statusClass }}">{{ $row['Estado'] }}</span></td>
                        <td><span class="course-name">{{ $row['Curso'] }}</span></td>
                        <td>{{ $row['Tema'] }}</td>
                        <td>{{ $row['Profesor'] }}</td>
                        <td>{{ $row['Aula'] }}</td>
                        <td>{{ $row['Inscritos'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="{{ count($headers) }}">
                            No hay registros para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>
</html>

