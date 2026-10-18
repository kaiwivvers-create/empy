<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports.index', $this->reportData($request));
    }

    public function pdf(Request $request)
    {
        $data = $this->reportData($request);

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->download('performance-report-'.now()->format('Ymd').'.pdf');
    }

    public function calibration(Request $request)
    {
        $periods = EvaluationPeriod::orderByDesc('start_date')->get();

        $period = $request->filled('period')
            ? $periods->firstWhere('id', $request->integer('period'))
            : ($periods->firstWhere('status', 'active') ?? $periods->first());

        $evaluations = Evaluation::with('evaluator')
            ->where('status', 'submitted')
            ->when($period, fn ($query) => $query->where('period_id', $period->id))
            ->get();

        $companyAverage = round((float) $evaluations->avg('final_score'), 1);

        $groups = $evaluations
            ->groupBy('evaluator_id')
            ->map(function (Collection $group) use ($companyAverage) {
                $average = round((float) $group->avg('final_score'), 1);

                return [
                    'name' => $group->first()->evaluator?->name ?? '-',
                    'count' => $group->count(),
                    'average' => $average,
                    'min' => round((float) $group->min('final_score'), 1),
                    'max' => round((float) $group->max('final_score'), 1),
                    'deviation' => round($average - $companyAverage, 1),
                    'low' => $group->filter(fn ($evaluation) => (float) $evaluation->final_score < 60)->count(),
                ];
            })
            ->sortByDesc('average')
            ->values();

        return view('reports.calibration', [
            'periods' => $periods,
            'period' => $period,
            'groups' => $groups,
            'companyAverage' => $companyAverage,
            'evaluated' => $evaluations->count(),
        ]);
    }

    public function excel(Request $request): StreamedResponse
    {
        $data = $this->reportData($request);
        $period = $data['period'];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(__('app.reports.ranking'));

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', __('app.reports.title'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', ($period?->name ?? '-').' · '.__('app.reports.generated_at').': '.now()->format('d/m/Y H:i'));

        $headers = [
            __('app.reports.rank'),
            __('app.employees.nip'),
            __('app.common.employee'),
            __('app.common.department'),
            __('app.common.position'),
            __('app.common.score'),
            __('app.common.grade'),
            __('app.common.evaluator'),
        ];

        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle('A4:H4')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 5;

        foreach ($data['ranking']->values() as $index => $evaluation) {
            $sheet->fromArray([
                $index + 1,
                $evaluation->employee->nip,
                $evaluation->employee->name,
                $evaluation->employee->department?->name ?? '-',
                $evaluation->employee->position?->name ?? '-',
                round((float) $evaluation->final_score, 2),
                Evaluation::gradeFor((float) $evaluation->final_score),
                $evaluation->evaluator?->name ?? '-',
            ], null, 'A'.$row);

            $row++;
        }

        $sheet->getStyle('A4:H'.max($row - 1, 4))->getBorders()->getAllBorders()->setBorderStyle('thin');
        $sheet->getStyle('A5:A'.max($row - 1, 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F5:G'.max($row - 1, 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $summaryRow = $row + 1;
        $sheet->setCellValue('A'.$summaryRow, __('app.reports.summary'));
        $sheet->getStyle('A'.$summaryRow)->getFont()->setBold(true);

        $summary = [
            [__('app.reports.evaluated'), $data['summary']['evaluated']],
            [__('app.reports.not_evaluated'), $data['summary']['notEvaluated']],
            [__('app.common.average'), round($data['summary']['average'], 2)],
            [__('app.reports.completion'), $data['summary']['completion'].'%'],
        ];

        $summaryRow++;

        foreach ($summary as $item) {
            $sheet->setCellValue('A'.$summaryRow, $item[0]);
            $sheet->setCellValue('B'.$summaryRow, $item[1]);
            $summaryRow++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'performance-report-'.now()->format('Ymd').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(Request $request): array
    {
        $periods = EvaluationPeriod::orderByDesc('start_date')->get();

        $period = $request->filled('period')
            ? $periods->firstWhere('id', $request->integer('period'))
            : ($periods->firstWhere('status', 'active') ?? $periods->first());

        $departmentId = $request->filled('department') ? $request->integer('department') : null;

        $scopeEmployees = Employee::query()
            ->active()
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId));

        $scopeIds = (clone $scopeEmployees)->pluck('id');

        $evaluations = Evaluation::query()
            ->with(['employee.department', 'employee.position', 'period', 'evaluator'])
            ->where('status', 'submitted')
            ->when($period, fn ($query) => $query->where('period_id', $period->id))
            ->when($departmentId, fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->where('department_id', $departmentId)))
            ->orderByDesc('final_score')
            ->get();

        $ranking = $evaluations;
        $evaluatedIds = $evaluations->pluck('employee_id');

        $notEvaluated = Employee::query()
            ->with(['department', 'position'])
            ->whereIn('id', $scopeIds->diff($evaluatedIds))
            ->orderBy('name')
            ->get();

        $totalEmployees = $scopeIds->count();
        $evaluatedCount = $ranking->count();

        $summary = [
            'totalEmployees' => $totalEmployees,
            'evaluated' => $evaluatedCount,
            'notEvaluated' => $notEvaluated->count(),
            'average' => (float) $ranking->avg('final_score'),
            'completion' => $totalEmployees > 0
                ? round($evaluatedCount / $totalEmployees * 100, 1)
                : 0.0,
        ];

        $byDepartment = $ranking
            ->groupBy(fn (Evaluation $evaluation) => $evaluation->employee->department?->name ?? '-')
            ->map(fn (Collection $group, string $name) => [
                'label' => $name,
                'count' => $group->count(),
                'average' => round((float) $group->avg('final_score'), 1),
            ])
            ->sortByDesc('average')
            ->values();

        $byCriteria = \App\Models\EvaluationScore::query()
            ->with('criterion')
            ->whereIn('evaluation_id', $ranking->pluck('id'))
            ->get()
            ->groupBy('criteria_id')
            ->map(function (Collection $group) {
                $criterion = $group->first()->criterion;

                return [
                    'label' => $criterion?->name ?? '-',
                    'weight' => (float) ($criterion?->weight ?? 0),
                    'average' => round((float) $group->avg('score'), 2),
                ];
            })
            ->sortByDesc('average')
            ->values();

        $distribution = $ranking
            ->groupBy(fn (Evaluation $evaluation) => Evaluation::gradeFor((float) $evaluation->final_score))
            ->map(fn (Collection $group, string $grade) => [
                'grade' => $grade,
                'label' => __('app.grades.'.$grade),
                'count' => $group->count(),
            ])
            ->sortKeys()
            ->values();

        return [
            'periods' => $periods,
            'period' => $period,
            'departments' => Department::orderBy('name')->get(),
            'departmentId' => $departmentId,
            'ranking' => $ranking,
            'summary' => $summary,
            'byDepartment' => $byDepartment,
            'byCriteria' => $byCriteria,
            'distribution' => $distribution,
            'notEvaluated' => $notEvaluated,
            'criteriaCount' => Criterion::active()->count(),
        ];
    }
}
