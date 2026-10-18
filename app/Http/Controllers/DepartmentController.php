<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::with('head')->withCount('employees')->orderBy('name')->paginate(10);

        [$openModal, $editItem] = $this->modalState($request);

        return view('departments.index', [
            'departments' => $departments,
            'employees' => Employee::active()->with('position')->orderBy('name')->get(),
            'openModal' => $openModal,
            'editItem' => $editItem,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('departments.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $department = Department::create($this->validated($request));

        ActivityLogger::record('created', 'Department created: '.$department->name, $department);

        return redirect()->route('departments.index')->with('success', __('app.departments.success_created'));
    }

    public function edit(Department $department): RedirectResponse
    {
        return redirect()->route('departments.index', ['open' => 'edit-'.$department->id]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $before = $department->getAttributes();

        $department->update($this->validated($request, $department));

        ActivityLogger::record(
            'updated',
            'Department updated: '.$department->name,
            $department,
            ActivityLogger::changes($department, $before),
        );

        return redirect()->route('departments.index')->with('success', __('app.departments.success_updated'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->with('error', __('app.departments.delete_blocked'));
        }

        ActivityLogger::record('deleted', 'Department deleted: '.$department->name, $department);

        $department->delete();

        return redirect()->route('departments.index')->with('success', __('app.departments.success_deleted'));
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('departments', 'code')->ignore($department)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'head_id' => ['nullable', 'exists:employees,id'],
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?Department}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = Department::find($matches[1]);
            $open = $editItem ? 'department-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'department-create';
        }

        return [$open ?: null, $editItem];
    }
}
