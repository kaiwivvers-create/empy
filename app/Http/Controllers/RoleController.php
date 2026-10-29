<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\PageAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        $defaults = PageAccess::defaults();
        $stored = RolePermission::query()->get()->groupBy('role');

        $usage = $roles->mapWithKeys(function (Role $role) use ($defaults, $stored) {
            if ($role->name === User::ROLE_SUPER_ADMIN) {
                return [$role->name => count(PageAccess::pages())];
            }

            $rows = $stored->get($role->name);

            $count = $rows
                ? $rows->where('is_allowed', true)->count()
                : collect($defaults)->filter(fn ($pages) => (bool) ($pages[$role->name] ?? false))->count();

            return [$role->name => $count];
        });

        return view('roles.index', [
            'roles' => $roles,
            'usage' => $usage,
            'bases' => Role::bases(),
            'pageCount' => count(PageAccess::pages()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'label_id' => $data['label_id'] ?? null,
            'base' => $data['base'],
            'is_system' => false,
        ]);

        PageAccess::registerRole($role, $data['base']);

        ActivityLogger::record('created', 'Role created: '.$role->name, $role);

        return redirect()->route('roles.index')->with('success', __('app.roles_page.success_created'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validated($request, $role);
        $before = $role->getAttributes();

        $role->update([
            'label' => $data['label'],
            'label_id' => $data['label_id'] ?? null,
            'base' => $data['base'],
        ]);

        ActivityLogger::record(
            'updated',
            'Role updated: '.$role->name,
            $role,
            ActivityLogger::changes($role, $before),
        );

        return redirect()->route('roles.index')->with('success', __('app.roles_page.success_updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('error', __('app.roles_page.cannot_delete_system'));
        }

        if (User::where('role', $role->name)->exists()) {
            return back()->with('error', __('app.roles_page.cannot_delete_used'));
        }

        $name = $role->name;

        ActivityLogger::record('deleted', 'Role deleted: '.$name, $role);

        $role->delete();
        PageAccess::forgetRole($name);

        return redirect()->route('roles.index')->with('success', __('app.roles_page.success_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Role $role = null): array
    {
        $input = $request->all();
        $input['name'] = Str::slug((string) $request->input('name'), '_');

        $request->merge(['name' => $input['name']]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:20',
                'regex:/^[a-z][a-z0-9_]{1,19}$/',
                Rule::unique('roles', 'name')->ignore($role),
            ],
            'label' => ['required', 'string', 'max:60'],
            'label_id' => ['nullable', 'string', 'max:60'],
            'base' => ['required', Rule::in(Role::bases())],
        ]);

        if ($role?->is_system && $data['base'] !== $role->base
            && in_array($role->name, ['super_admin', 'admin', 'manager', 'employee'], true)) {
            throw ValidationException::withMessages([
                'base' => __('app.roles_page.base_locked'),
            ]);
        }

        return $data;
    }
}
