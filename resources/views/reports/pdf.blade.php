<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('app.reports.title') }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 0; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { color: #4f46e5; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; }
        h1 { font-size: 20px; margin: 4px 0 2px; color: #0f172a; }
        .muted { color: #64748b; }
        .meta { margin-top: 6px; font-size: 10px; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th { background: #eef2ff; color: #3730a3; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 7px 6px; text-align: left; border-bottom: 1px solid #c7d2fe; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .center { text-align: center; }
        .right { text-align: right; }
        .summary td { border: none; padding: 4px 6px; }
        .summary .value { font-weight: bold; color: #0f172a; }
        .cards { width: 100%; margin-bottom: 16px; }
        .cards td { width: 25%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center; background: #f8fafc; }
        .cards .number { font-size: 18px; font-weight: bold; color: #0f172a; display: block; }
        .cards .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        h2 { font-size: 12px; color: #0f172a; margin: 16px 0 8px; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 9px; font-size: 9px; font-weight: bold; background: #e0e7ff; color: #3730a3; }
        .signature { margin-top: 32px; width: 100%; }
        .signature td { border: none; text-align: center; padding-top: 40px; }
        .line { border-top: 1px solid #94a3b8; width: 160px; margin: 0 auto; padding-top: 5px; font-size: 10px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ \App\Support\Brand::name() }} · {{ \App\Support\Brand::tagline() }}</div>
        <h1>{{ __('app.reports.title') }}</h1>
        <div class="meta">
            {{ __('app.common.period') }}: <strong>{{ $period?->name ?? '-' }}</strong>
            &nbsp;·&nbsp;
            {{ __('app.common.department') }}: <strong>{{ $departmentId ? ($departments->firstWhere('id', $departmentId)?->name ?? '-') : __('app.reports.all_departments') }}</strong>
            &nbsp;·&nbsp;
            {{ __('app.reports.generated_at') }}: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="cards">
        <tr>
            <td><span class="number">{{ $summary['totalEmployees'] }}</span><span class="label">{{ __('app.dashboard.total_employees') }}</span></td>
            <td><span class="number">{{ $summary['evaluated'] }}</span><span class="label">{{ __('app.reports.evaluated') }}</span></td>
            <td><span class="number">{{ number_format($summary['average'], 1) }}</span><span class="label">{{ __('app.common.average') }}</span></td>
            <td><span class="number">{{ number_format($summary['completion'], 1) }}%</span><span class="label">{{ __('app.reports.completion') }}</span></td>
        </tr>
    </table>

    <h2>{{ __('app.reports.ranking') }}</h2>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 30px;">#</th>
                <th>{{ __('app.employees.nip') }}</th>
                <th>{{ __('app.common.name') }}</th>
                <th>{{ __('app.common.department') }}</th>
                <th>{{ __('app.common.position') }}</th>
                <th class="center">{{ __('app.common.score') }}</th>
                <th class="center">{{ __('app.common.grade') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ranking as $index => $evaluation)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $evaluation->employee?->nip }}</td>
                    <td>{{ $evaluation->employee?->name }}</td>
                    <td>{{ $evaluation->employee?->department?->name ?? '-' }}</td>
                    <td>{{ $evaluation->employee?->position?->name ?? '-' }}</td>
                    <td class="center"><strong>{{ number_format((float) $evaluation->final_score, 1) }}</strong></td>
                    <td class="center"><span class="badge">{{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="center muted">{{ __('app.reports.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <table style="width: 100%;">
        <tr>
            <td style="width: 48%; border: none; vertical-align: top; padding: 0;">
                <h2>{{ __('app.reports.by_department') }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.common.department') }}</th>
                            <th class="center">{{ __('app.reports.evaluated') }}</th>
                            <th class="center">{{ __('app.common.average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byDepartment as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="center">{{ $row['count'] }}</td>
                                <td class="center">{{ number_format($row['average'], 1) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="center muted">{{ __('app.reports.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td style="width: 4%; border: none;"></td>
            <td style="width: 48%; border: none; vertical-align: top; padding: 0;">
                <h2>{{ __('app.reports.by_criteria') }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.criteria.title') }}</th>
                            <th class="center">{{ __('app.common.weight') }}</th>
                            <th class="center">{{ __('app.common.average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byCriteria as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="center">{{ rtrim(rtrim(number_format($row['weight'], 2), '0'), '.') }}%</td>
                                <td class="center">{{ number_format($row['average'], 2) }}/5</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="center muted">{{ __('app.reports.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    @if ($notEvaluated->isNotEmpty())
        <h2>{{ __('app.reports.not_evaluated_list') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('app.employees.nip') }}</th>
                    <th>{{ __('app.common.name') }}</th>
                    <th>{{ __('app.common.department') }}</th>
                    <th>{{ __('app.common.position') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notEvaluated as $employee)
                    <tr>
                        <td>{{ $employee->nip }}</td>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->department?->name ?? '-' }}</td>
                        <td>{{ $employee->position?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="signature">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%;">
                {{ __('app.reports.generated_at') }}: {{ now()->translatedFormat('d F Y') }}
                <div class="line">{{ __('app.reports.signature') }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
