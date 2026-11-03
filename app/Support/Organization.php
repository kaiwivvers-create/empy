<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Employee;

class Organization
{
    /**
     * Keep a department member reporting to their department head. The head
     * keeps their own manager, and cycles are never introduced.
     */
    public static function syncManagerWithDepartmentHead(Employee $employee): void
    {
        $headId = $employee->department_id
            ? Department::whereKey($employee->department_id)->value('head_id')
            : null;

        $headId = $headId ? (int) $headId : null;

        if (! $headId || $headId === $employee->id || $employee->manager_id === $headId) {
            return;
        }

        if (static::reportsTo($headId, $employee->id)) {
            return;
        }

        $employee->update(['manager_id' => $headId]);
    }

    /**
     * Point every member of a department at its (new) head.
     */
    public static function applyDepartmentHead(Department $department): void
    {
        if (! $department->head_id) {
            return;
        }

        $department->employees()
            ->whereKeyNot($department->head_id)
            ->get()
            ->each(fn (Employee $member) => static::syncManagerWithDepartmentHead($member));
    }

    /**
     * Whether $managerId sits somewhere above $employeeId in the chain.
     */
    public static function reportsTo(int $employeeId, int $managerId): bool
    {
        $current = Employee::find($employeeId);
        $guard = 0;

        while ($current && $guard++ < 200) {
            if ($current->id === $managerId) {
                return true;
            }

            $current = $current->manager_id ? Employee::find($current->manager_id) : null;
        }

        return false;
    }
}
