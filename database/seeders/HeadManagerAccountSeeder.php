<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;

class HeadManagerAccountSeeder extends Seeder
{
    private const PASSWORD = 'kai250510';

    public function run(): void
    {
        $department = Department::query()->orderBy('id')->first();
        $position = Position::firstOrCreate(
            ['name' => 'Head Manager'],
            ['level' => 'Manager'],
        );

        foreach ([
            ['name' => 'Dawn', 'email' => 'dawn@example.com', 'nip' => 'EMP-HM-001'],
            ['name' => 'Stotch', 'email' => 'stotch@example.com', 'nip' => 'EMP-HM-002'],
        ] as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => self::PASSWORD,
                    'role' => User::ROLE_HEAD_MANAGER,
                    'is_active' => true,
                ],
            );

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nip' => $data['nip'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'department_id' => $department?->id,
                    'position_id' => $position->id,
                    'status' => 'active',
                ],
            );
        }
    }
}
