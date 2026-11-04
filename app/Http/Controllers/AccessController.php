<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Support\ActivityLogger;
use App\Support\PageAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function index(): View
    {
        return view('settings.access', [
            'pages' => PageAccess::pages(),
            'matrix' => PageAccess::matrix(),
            'defaults' => PageAccess::defaults(),
            'roles' => PageAccess::matrixRoles(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
        ]);

        $permissions = $data['permissions'] ?? [];
        $defaults = PageAccess::defaults();

        foreach (PageAccess::matrixRoles() as $role) {
            foreach (array_keys(PageAccess::pages()) as $page) {
                $allowed = (bool) ($permissions[$role][$page] ?? false);

                // Only store values that differ from the defaults, so later
                // default changes still reach roles without a custom choice.
                if ($allowed === (bool) ($defaults[$page][$role] ?? false)) {
                    RolePermission::query()
                        ->where('role', $role)
                        ->where('permission', $page)
                        ->delete();

                    continue;
                }

                RolePermission::updateOrCreate(
                    ['role' => $role, 'permission' => $page],
                    ['is_allowed' => $allowed],
                );
            }
        }

        ActivityLogger::record('updated', 'User access permissions updated');

        return back()->with('success', __('app.access.success_updated'));
    }

    public function updateDefaults(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
        ]);

        PageAccess::saveDefaults($data['permissions'] ?? []);

        ActivityLogger::record('updated', 'Default user access permissions updated');

        return back()->with('success', __('app.access.success_defaults'));
    }

    public function reset(): RedirectResponse
    {
        PageAccess::resetToDefaults();

        ActivityLogger::record('updated', 'User access matrix reset to defaults');

        return back()->with('success', __('app.access.success_reset'));
    }
}
