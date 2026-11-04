<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $projects = Project::query()
            ->visibleTo($user)
            ->with(['owner.employee', 'members.position'])
            ->withCount('members')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        [$openModal, $editItem] = $this->modalState($request);

        return view('projects.index', [
            'projects' => $projects,
            'filters' => $request->only('search', 'status'),
            'canCreate' => $this->canCreate($user),
            'memberOptions' => $this->memberOptions($user),
            'openModal' => $openModal,
            'editItem' => $editItem,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanCreate($user);

        $data = $this->validated($request);
        $members = $this->memberIds($user, $request->input('members', []));

        $project = DB::transaction(function () use ($data, $members, $user) {
            $project = Project::create([
                ...$data,
                'owner_id' => $user->id,
                'created_by' => $user->id,
            ]);

            $project->members()->sync($members);

            return $project;
        });

        ActivityLogger::record('created', 'Project created: '.$project->name, $project);

        return $this->redirectToIndex('projects.index', ['search', 'status', 'page'])
            ->with('success', __('app.projects.success_created'));
    }

    public function show(Request $request, Project $project): View
    {
        $user = $request->user();

        if (! $project->isVisibleTo($user)) {
            abort(403, __('app.common.no_access'));
        }

        $project->load(['owner.employee', 'creator', 'members.position', 'members.department']);

        return view('projects.show', [
            'project' => $project,
            'canManage' => $project->canBeManagedBy($user),
            'memberOptions' => $this->memberOptions($user, $project),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->ensureCanManage($request->user(), $project);

        $data = $this->validated($request);
        $before = $project->getAttributes();

        $project->update($data);

        ActivityLogger::record(
            'updated',
            'Project updated: '.$project->name,
            $project,
            ActivityLogger::changes($project, $before),
        );

        return $this->redirectToIndex('projects.index', ['search', 'status', 'page'])
            ->with('success', __('app.projects.success_updated'));
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->ensureCanManage($request->user(), $project);

        ActivityLogger::record('deleted', 'Project deleted: '.$project->name, $project);

        $project->delete();

        return $this->redirectToIndex('projects.index', ['search', 'status', 'page'])
            ->with('success', __('app.projects.success_deleted'));
    }

    public function addMember(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManage($user, $project);

        $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $employeeId = (int) $request->input('employee_id');
        $this->memberIds($user, [$employeeId]);

        $project->members()->syncWithoutDetaching([$employeeId]);

        ActivityLogger::record('updated', 'Project member added: '.$project->name, $project);

        return back()->with('success', __('app.projects.success_member_added'));
    }

    public function removeMember(Request $request, Project $project, Employee $employee): RedirectResponse
    {
        $this->ensureCanManage($request->user(), $project);

        if (! $project->isMember($employee)) {
            return back()->with('error', __('app.projects.member_not_found'));
        }

        $project->members()->detach($employee->id);

        ActivityLogger::record('updated', 'Project member removed: '.$project->name, $project);

        return back()->with('success', __('app.projects.success_member_removed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(Project::statuses())],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function memberIds(User $user, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === [] || $user->isAdmin()) {
            return $ids;
        }

        // Managers may only add people who report to them directly.
        $allowed = $user->employee
            ? Employee::query()
                ->where('manager_id', $user->employee->id)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all()
            : [];

        if (count($allowed) !== count($ids)) {
            throw ValidationException::withMessages([
                'members' => __('app.projects.member_not_allowed'),
            ]);
        }

        return array_map('intval', $allowed);
    }

    /**
     * @return Collection<int, Employee>
     */
    private function memberOptions(User $user, ?Project $project = null): Collection
    {
        if (! $this->canCreate($user) && ! $user->isAdmin()) {
            return collect();
        }

        return Employee::query()
            ->active()
            ->with('position')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('manager_id', $user->employee?->id))
            ->when($project, function ($query) use ($project) {
                $query->whereNotIn('id', $project->members()->pluck('employees.id'));
            })
            ->orderBy('name')
            ->get();
    }

    private function canCreate(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    private function ensureCanCreate(User $user): void
    {
        if (! $this->canCreate($user)) {
            abort(403, __('app.common.no_access'));
        }
    }

    private function ensureCanManage(User $user, Project $project): void
    {
        if (! $project->canBeManagedBy($user)) {
            abort(403, __('app.common.no_access'));
        }
    }

    /**
     * @return array{0: ?string, 1: ?Project}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = Project::find($matches[1]);

            if ($editItem && ! $editItem->canBeManagedBy($request->user())) {
                $editItem = null;
            }

            $open = $editItem ? 'project-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'project-create';
        }

        return [$open ?: null, $editItem];
    }
}
