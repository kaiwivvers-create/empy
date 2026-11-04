<?php

namespace App\Support;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\User;

class PageAccess
{
    /**
     * Pages that can be granted to roles.
     *
     * @return array<string, array{group: string, label: string}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['group' => 'main', 'label' => 'app.nav.dashboard'],
            'employees' => ['group' => 'main', 'label' => 'app.nav.employees'],
            'evaluations' => ['group' => 'main', 'label' => 'app.nav.evaluations'],
            'kpi' => ['group' => 'main', 'label' => 'app.kpi.title'],
            'projects' => ['group' => 'main', 'label' => 'app.projects.title'],
            'feedback' => ['group' => 'main', 'label' => 'app.feedback.title'],
            'reports' => ['group' => 'main', 'label' => 'app.nav.reports'],
            'organization' => ['group' => 'main', 'label' => 'app.organization.title'],
            'departments' => ['group' => 'organization', 'label' => 'app.nav.departments'],
            'positions' => ['group' => 'organization', 'label' => 'app.nav.positions'],
            'periods' => ['group' => 'evaluation_setup', 'label' => 'app.nav.periods'],
            'criteria' => ['group' => 'evaluation_setup', 'label' => 'app.nav.criteria'],
            'users' => ['group' => 'administration', 'label' => 'app.users.title'],
            'roles_page' => ['group' => 'administration', 'label' => 'app.roles_page.title'],
            'activity' => ['group' => 'administration', 'label' => 'app.activity.title'],
        ];
    }

    /**
     * Roles shown in the access matrix, excluding the super admin.
     *
     * @return list<string>
     */
    public static function matrixRoles(): array
    {
        return array_values(array_filter(
            Role::allCached()->pluck('name')->all(),
            fn (string $role) => $role !== User::ROLE_SUPER_ADMIN,
        ));
    }

    /**
     * Factory defaults used when the super admin has not customised them.
     *
     * @return array<string, array<string, bool>>
     */
    public static function builtInDefaults(): array
    {
        return [
            'dashboard' => ['admin' => true, 'manager' => true, 'employee' => true],
            'employees' => ['admin' => true, 'manager' => true, 'employee' => true],
            'evaluations' => ['admin' => true, 'manager' => true, 'employee' => true],
            'kpi' => ['admin' => true, 'manager' => true, 'employee' => true],
            'projects' => ['admin' => true, 'manager' => true, 'employee' => true],
            'feedback' => ['admin' => true, 'manager' => true, 'employee' => true],
            'reports' => ['admin' => true, 'manager' => true, 'employee' => false],
            'organization' => ['admin' => true, 'manager' => true, 'employee' => false],
            'departments' => ['admin' => true, 'manager' => true, 'employee' => false],
            'positions' => ['admin' => true, 'manager' => true, 'employee' => false],
            'periods' => ['admin' => true, 'manager' => false, 'employee' => false],
            'criteria' => ['admin' => true, 'manager' => false, 'employee' => false],
            'users' => ['admin' => true, 'manager' => false, 'employee' => false],
            'roles_page' => ['admin' => false, 'manager' => false, 'employee' => false],
            'activity' => ['admin' => true, 'manager' => false, 'employee' => false],
        ];
    }

    /**
     * Current defaults (factory values merged with the super admin's edits).
     *
     * @return array<string, array<string, bool>>
     */
    public static function defaults(): array
    {
        $defaults = static::builtInDefaults();
        $stored = json_decode((string) Setting::get('access_defaults'), true);

        if (is_array($stored)) {
            foreach (array_keys(static::pages()) as $page) {
                if (! isset($stored[$page]) || ! is_array($stored[$page])) {
                    continue;
                }

                foreach ($stored[$page] as $role => $allowed) {
                    $defaults[$page][$role] = (bool) $allowed;
                }
            }
        }

        foreach (static::matrixRoles() as $role) {
            foreach (array_keys(static::pages()) as $page) {
                $defaults[$page][$role] ??= false;
            }
        }

        return $defaults;
    }

    /**
     * Persist a custom default matrix.
     *
     * @param  array<string, array<string, mixed>>  $permissions
     */
    public static function saveDefaults(array $permissions): void
    {
        $matrix = [];

        foreach (array_keys(static::pages()) as $page) {
            foreach (static::matrixRoles() as $role) {
                $matrix[$page][$role] = (bool) ($permissions[$page][$role] ?? false);
            }
        }

        Setting::put('access_defaults', json_encode($matrix));

        // Drop overrides that now match the new default so the updated
        // default is what every role actually receives.
        RolePermission::query()->get()->each(function (RolePermission $permission) use ($matrix) {
            $page = $permission->permission;
            $role = $permission->role;

            if (isset($matrix[$page][$role]) && (bool) $permission->is_allowed === $matrix[$page][$role]) {
                $permission->delete();
            }
        });
    }

    /**
     * Remove every stored override so the current defaults apply again.
     */
    public static function resetToDefaults(): void
    {
        RolePermission::query()->delete();
    }

    /**
     * Give a freshly created role the defaults of a template role.
     */
    public static function registerRole(Role $role, string $template): void
    {
        $defaults = static::defaults();
        $matrix = [];

        foreach (array_keys(static::pages()) as $page) {
            foreach (static::matrixRoles() as $name) {
                $matrix[$page][$name] = $name === $role->name
                    ? (bool) ($defaults[$page][$template] ?? false)
                    : (bool) ($defaults[$page][$name] ?? false);
            }
        }

        Setting::put('access_defaults', json_encode($matrix));
    }

    /**
     * Drop a deleted role from the stored defaults.
     */
    public static function forgetRole(string $name): void
    {
        $matrix = static::defaults();
        $payload = [];

        foreach ($matrix as $page => $roles) {
            unset($roles[$name]);
            $payload[$page] = $roles;
        }

        Setting::put('access_defaults', json_encode($payload));
    }

    public static function allows(?User $user, string $page): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! array_key_exists($page, static::pages())) {
            return false;
        }

        $stored = RolePermission::query()
            ->where('role', $user->role)
            ->where('permission', $page)
            ->first();

        if ($stored) {
            return $stored->is_allowed;
        }

        return (bool) (static::defaults()[$page][$user->role] ?? false);
    }

    /**
     * Full current matrix for the access page.
     *
     * @return array<string, array<string, bool>>
     */
    public static function matrix(): array
    {
        $matrix = [];

        foreach (static::matrixRoles() as $role) {
            foreach (array_keys(static::pages()) as $page) {
                $matrix[$role][$page] = static::allows(
                    new User(['role' => $role]),
                    $page,
                );
            }
        }

        return $matrix;
    }

    /**
     * Map a route name to the page permission it belongs to.
     */
    public static function routePermission(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        return match (true) {
            $routeName === 'dashboard' => 'dashboard',
            str_starts_with($routeName, 'employees.') => 'employees',
            str_starts_with($routeName, 'evaluations.') => 'evaluations',
            str_starts_with($routeName, 'kpi.') => 'kpi',
            str_starts_with($routeName, 'projects.') => 'projects',
            str_starts_with($routeName, 'feedback.') => 'feedback',
            str_starts_with($routeName, 'reports.') => 'reports',
            str_starts_with($routeName, 'organization.') => 'organization',
            str_starts_with($routeName, 'departments.') => 'departments',
            str_starts_with($routeName, 'positions.') => 'positions',
            str_starts_with($routeName, 'periods.') => 'periods',
            str_starts_with($routeName, 'criteria.') => 'criteria',
            str_starts_with($routeName, 'users.') => 'users',
            str_starts_with($routeName, 'roles.') => 'roles_page',
            str_starts_with($routeName, 'activity.') => 'activity',
            default => null,
        };
    }

    /** Landing route for a user based on the pages they can open. */
    public static function homeRoute(User $user): string
    {
        $routes = [
            'dashboard' => 'dashboard',
            'employees' => 'employees.index',
            'evaluations' => 'evaluations.index',
            'kpi' => 'kpi.index',
            'projects' => 'projects.index',
            'feedback' => 'feedback.index',
            'reports' => 'reports.index',
            'organization' => 'organization.chart',
            'departments' => 'departments.index',
            'positions' => 'positions.index',
            'periods' => 'periods.index',
            'criteria' => 'criteria.index',
            'users' => 'users.index',
            'roles_page' => 'roles.index',
            'activity' => 'activity.index',
        ];

        foreach ($routes as $page => $route) {
            if (! static::allows($user, $page)) {
                continue;
            }

            // Admins and managers do their evaluation work on the team page;
            // the personal tracking list is an employee view.
            if ($page === 'evaluations' && ! $user->isEmployee()) {
                return 'evaluations.team';
            }

            return $route;
        }

        return 'dashboard';
    }
}
