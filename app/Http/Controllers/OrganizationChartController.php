<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationChartController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::with([
            'head.position',
            'employees' => fn ($query) => $query->active()->with(['position', 'manager', 'user', 'headedDepartment'])->orderBy('name'),
        ])->orderBy('name')->get();

        $employees = Employee::active()->with(['position', 'user', 'headedDepartment'])->orderBy('name')->get();

        return view('organization.chart', [
            'departments' => $departments,
            'roots' => Employee::active()
                ->whereNull('manager_id')
                ->with(['position', 'department'])
                ->orderBy('name')
                ->get(),
            'totalEmployees' => Employee::active()->count(),
            'canEdit' => $request->user()->isAdmin(),
            'employees' => $employees,
            'managerEmployees' => $employees
                ->filter(fn (Employee $employee) => $employee->isManagerAccount())
                ->merge($departments->pluck('head')->filter())
                ->unique('id')
                ->sortBy('name')
                ->values(),
            'positions' => Position::orderBy('name')->get(),
            'accountRoles' => Role::query()
                ->where('name', '!=', User::ROLE_SUPER_ADMIN)
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (Role $role) => [$role->name => $role->displayLabel()])
                ->all(),
        ]);
    }

    public function updateHead(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'head_id' => ['nullable', 'exists:employees,id'],
        ]);

        $headId = isset($data['head_id']) ? (int) $data['head_id'] : null;

        // One person can only lead one department.
        if ($headId && Department::where('head_id', $headId)->whereKeyNot($department->id)->exists()) {
            return back()->with('error', __('app.organization.head_taken'));
        }

        $before = $department->getAttributes();

        $department->update(['head_id' => $headId]);

        Organization::applyDepartmentHead($department);

        ActivityLogger::record(
            'updated',
            'Department head updated: '.$department->name,
            $department,
            ActivityLogger::changes($department, $before),
        );

        return back()->with('success', __('app.organization.success_head'));
    }

    public function updateManager(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'manager_id' => ['nullable', 'exists:employees,id', Rule::notIn([$employee->id])],
        ]);

        $managerId = isset($data['manager_id']) ? (int) $data['manager_id'] : null;

        if ($managerId) {
            $manager = Employee::with('user')->find($managerId);

            if (! $manager || ! $manager->isManagerAccount()) {
                throw ValidationException::withMessages([
                    'manager_id' => __('app.organization.manager_not_leader'),
                ]);
            }

            if (Organization::reportsTo($managerId, $employee->id)) {
                throw ValidationException::withMessages([
                    'manager_id' => __('app.organization.manager_cycle'),
                ]);
            }
        }

        // Everyone inside a department reports to its head; only the head may
        // keep a different manager (the person above them).
        $headId = $employee->department_id
            ? Department::whereKey($employee->department_id)->value('head_id')
            : null;

        if ($headId && (int) $headId !== $employee->id) {
            $managerId = (int) $headId;
        }

        $before = $employee->getAttributes();

        $employee->update(['manager_id' => $managerId]);

        ActivityLogger::record(
            'updated',
            'Manager updated for employee: '.$employee->name,
            $employee,
            ActivityLogger::changes($employee, $before),
        );

        return back()->with('success', __('app.organization.success_manager'));
    }
}
