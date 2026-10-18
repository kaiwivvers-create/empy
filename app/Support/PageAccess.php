<?php

namespace App\Support;

use App\Models\RolePermission;
use App\Models\User;

class PageAccess
{
    /** Roles shown in the access matrix. Super admins are intentionally excluded. */
    public const MATRIX_ROLES = ['admin', 'manager', 'employee'];

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
            'feedback' => ['group' => 'main', 'label' => 'app.feedback.title'],
            'reports' => ['group' => 'main', 'label' => 'app.nav.reports'],
            'organization' => ['group' => 'main', 'label' => 'app.organization.title'],
            'departments' => ['group' => 'organization', 'label' => 'app.nav.departments'],
            'positions' => ['group' => 'organization', 'label' => 'app.nav.positions'],
            'periods' => ['group' => 'evaluation_setup', 'label' => 'app.nav.periods'],
            'criteria' => ['group' => 'evaluation_setup', 'label' => 'app.nav.criteria'],
            'users' => ['group' => 'administration', 'label' => 'app.users.title'],
            'activity' => ['group' => 'administration', 'label' => 'app.activity.title'],
        ];
    }

    /**
     * Default access used until a super admin saves the matrix.
     *
     * @return array<string, array<string, bool>>
     */
    public static function defaults(): array
    {
        return [
            'dashboard' => ['admin' => true, 'manager' => true, 'employee' => true],
            'employees' => ['admin' => true, 'manager' => true, 'employee' => true],
            'evaluations' => ['admin' => true, 'manager' => true, 'employee' => true],
            'feedback' => ['admin' => true, 'manager' => true, 'employee' => true],
            'reports' => ['admin' => true, 'manager' => true, 'employee' => false],
            'organization' => ['admin' => true, 'manager' => true, 'employee' => false],
            'departments' => ['admin' => true, 'manager' => false, 'employee' => false],
            'positions' => ['admin' => true, 'manager' => false, 'employee' => false],
            'periods' => ['admin' => true, 'manager' => false, 'employee' => false],
            'criteria' => ['admin' => true, 'manager' => false, 'employee' => false],
            'users' => ['admin' => true, 'manager' => false, 'employee' => false],
            'activity' => ['admin' => true, 'manager' => false, 'employee' => false],
        ];
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
     * Full matrix for the access page.
     *
     * @return array<string, array<string, bool>>
     */
    public static function matrix(): array
    {
        $matrix = [];

        foreach (static::MATRIX_ROLES as $role) {
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
            str_starts_with($routeName, 'feedback.') => 'feedback',
            str_starts_with($routeName, 'reports.') => 'reports',
            str_starts_with($routeName, 'organization.') => 'organization',
            str_starts_with($routeName, 'departments.') => 'departments',
            str_starts_with($routeName, 'positions.') => 'positions',
            str_starts_with($routeName, 'periods.') => 'periods',
            str_starts_with($routeName, 'criteria.') => 'criteria',
            str_starts_with($routeName, 'users.') => 'users',
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
            'feedback' => 'feedback.index',
            'reports' => 'reports.index',
            'organization' => 'organization.chart',
            'departments' => 'departments.index',
            'positions' => 'positions.index',
            'periods' => 'periods.index',
            'criteria' => 'criteria.index',
            'users' => 'users.index',
            'activity' => 'activity.index',
        ];

        foreach ($routes as $page => $route) {
            if (static::allows($user, $page)) {
                return $route;
            }
        }

        return 'dashboard';
    }
}
