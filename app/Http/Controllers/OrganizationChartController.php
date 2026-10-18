<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\View\View;

class OrganizationChartController extends Controller
{
    public function index(): View
    {
        $departments = Department::with([
            'head.position',
            'employees' => fn ($query) => $query->active()->with(['position', 'manager'])->orderBy('name'),
        ])->orderBy('name')->get();

        return view('organization.chart', [
            'departments' => $departments,
            'roots' => Employee::active()
                ->whereNull('manager_id')
                ->with(['position', 'department'])
                ->orderBy('name')
                ->get(),
            'totalEmployees' => Employee::active()->count(),
        ]);
    }
}
