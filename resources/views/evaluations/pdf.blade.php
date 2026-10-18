<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('app.evaluations.detail_title') }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e293b; font-size: 11px; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { color: #4f46e5; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; }
        h1 { font-size: 18px; margin: 4px 0 2px; color: #0f172a; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th { background: #eef2ff; color: #3730a3; font-size: 9px; text-transform: uppercase; padding: 6px; text-align: left; border-bottom: 1px solid #c7d2fe; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .center { text-align: center; }
        .profile td { border: none; padding: 3px 6px; }
        .profile .label { color: #64748b; width: 90px; }
        h2 { font-size: 12px; color: #0f172a; margin: 16px 0 8px; }
        .score-box { border: 2px solid #4f46e5; border-radius: 8px; padding: 14px; text-align: center; margin-bottom: 16px; }
        .score-box .number { font-size: 32px; font-weight: bold; color: #4f46e5; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9px; font-size: 9px; font-weight: bold; background: #e0e7ff; color: #3730a3; }
        .signature td { border: none; text-align: center; padding-top: 46px; }
        .line { border-top: 1px solid #94a3b8; width: 180px; margin: 0 auto; padding-top: 5px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ \App\Support\Brand::name() }} · {{ \App\Support\Brand::tagline() }}</div>
        <h1>{{ __('app.evaluations.detail_title') }}</h1>
        <div class="muted">{{ $evaluation->period?->name }} · {{ __('app.reports.generated_at') }}: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="profile">
        <tr>
            <td class="label">{{ __('app.common.name') }}</td>
            <td><strong>{{ $evaluation->employee?->name }}</strong></td>
            <td class="label">{{ __('app.employees.nip') }}</td>
            <td>{{ $evaluation->employee?->nip }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('app.common.department') }}</td>
            <td>{{ $evaluation->employee?->department?->name ?? '-' }}</td>
            <td class="label">{{ __('app.common.position') }}</td>
            <td>{{ $evaluation->employee?->position?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('app.common.evaluator') }}</td>
            <td>{{ $evaluation->evaluator?->name ?? '-' }}</td>
            <td class="label">{{ __('app.common.date') }}</td>
            <td>{{ $evaluation->submitted_at?->format('d/m/Y') ?? '-' }}</td>
        </tr>
    </table>

    <div class="score-box">
        <div class="muted">{{ __('app.evaluations.final_score') }}</div>
        <div class="number">{{ $evaluation->isSubmitted() ? number_format((float) $evaluation->final_score, 1) : '—' }}</div>
        @if ($evaluation->isSubmitted())
            <div class="badge">
                {{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}
                · {{ __('app.grades.'.\App\Models\Evaluation::gradeFor((float) $evaluation->final_score)) }}
            </div>
        @endif
        @if ($evaluation->manager_score !== null && $evaluation->peerScore() !== null)
            <div class="muted" style="margin-top: 6px;">
                {{ __('app.feedback.manager_score') }}: {{ number_format((float) $evaluation->manager_score, 1) }}
                · {{ __('app.feedback.peer_score') }}: {{ number_format((float) $evaluation->peerScore(), 1) }}
            </div>
        @endif
    </div>

    <h2>{{ __('app.evaluations.scores') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('app.criteria.title') }}</th>
                <th class="center" style="width: 60px;">{{ __('app.common.weight') }}</th>
                <th class="center" style="width: 60px;">{{ __('app.common.score') }}</th>
                <th>{{ __('app.common.comment') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($scores as $score)
                <tr>
                    <td>{{ $score->criterion?->name }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format((float) $score->criterion?->weight, 2), '0'), '.') }}%</td>
                    <td class="center"><strong>{{ $score->score }}</strong> / 5</td>
                    <td>{{ $score->comment ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center muted">{{ __('app.common.not_yet') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @php $feedbacks = $evaluation->peerFeedbacks->filter(fn ($item) => $item->isSubmitted()); @endphp
    @if ($feedbacks->isNotEmpty())
        <h2>{{ __('app.feedback.request_360') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('app.feedback.reviewer') }}</th>
                    <th>{{ __('app.feedback.relationship') }}</th>
                    <th class="center">{{ __('app.common.score') }}</th>
                    <th>{{ __('app.common.comment') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($feedbacks as $feedback)
                    <tr>
                        <td>{{ $feedback->reviewer?->name }}</td>
                        <td>{{ __('app.feedback.relationships.'.$feedback->relationship) }}</td>
                        <td class="center">{{ $feedback->score }} / 5</td>
                        <td>{{ $feedback->comment ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($evaluation->notes)
        <h2>{{ __('app.common.notes') }}</h2>
        <p>{{ $evaluation->notes }}</p>
    @endif

    <table class="signature">
        <tr>
            <td style="width: 50%;">
                {{ __('app.common.evaluator') }}
                <div class="line">{{ $evaluation->evaluator?->name ?? '—' }}</div>
            </td>
            <td style="width: 50%;">
                {{ __('app.reports.signature') }}
                <div class="line">HRD</div>
            </td>
        </tr>
    </table>
</body>
</html>
