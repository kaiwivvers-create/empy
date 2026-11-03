<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmployeeAccountSeeder extends Seeder
{
    public const PASSWORD = '250510';

    /**
     * Give every employee that has no login yet a working account, so the
     * Users page lists the actual staff instead of only the demo logins.
     */
    public function run(): void
    {
        Employee::query()
            ->with('position')
            ->whereNull('user_id')
            ->orderBy('nip')
            ->get()
            ->each(function (Employee $employee) {
                $email = $this->emailFor($employee);

                $user = User::create([
                    'name' => $employee->name,
                    'email' => $email,
                    'password' => self::PASSWORD,
                    'role' => $employee->position?->level === 'Manager'
                        ? User::ROLE_MANAGER
                        : User::ROLE_EMPLOYEE,
                    'is_active' => true,
                ]);

                $employee->forceFill([
                    'user_id' => $user->id,
                    'email' => $user->email,
                ])->save();
            });
    }

    private function emailFor(Employee $employee): string
    {
        $email = Str::slug($employee->name).'@example.com';

        if (User::where('email', $email)->exists()) {
            $email = Str::slug($employee->name).'-'.Str::lower($employee->nip).'@example.com';
        }

        return $email;
    }
}
