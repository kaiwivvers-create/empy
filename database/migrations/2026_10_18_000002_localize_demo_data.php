<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('users')->where('email', 'kai@example.com')->exists()) {
            return;
        }

        DB::table('users')->where('email', 'kai@example.com')->update([
            'name' => 'Kai',
            'role' => 'super_admin',
            'updated_at' => now(),
        ]);

        foreach (['chips@example.com' => 'Chips', 'tweek@example.com' => 'Tweek', 'craig@example.com' => 'Craig'] as $email => $name) {
            DB::table('users')->where('email', $email)->update(['name' => $name, 'updated_at' => now()]);
        }

        $employees = [
            'EMP-001' => ['Kai', 'kai@example.com'],
            'EMP-002' => ['Chips', 'chips@example.com'],
            'EMP-003' => ['Ren', 'ren@empy.test'],
            'EMP-004' => ['Kael', 'kael@empy.test'],
            'EMP-005' => ['Aster', 'aster@empy.test'],
            'EMP-006' => ['Tweek', 'tweek@example.com'],
            'EMP-007' => ['Van', 'van@empy.test'],
            'EMP-008' => ['Lysra', 'lysra@empy.test'],
            'EMP-009' => ['Liu', 'liu@empy.test'],
            'EMP-010' => ['LiYou', 'liyou@empy.test'],
            'EMP-011' => ['Mira', 'mira@empy.test'],
            'EMP-012' => ['Juno', 'juno@empy.test'],
            'EMP-013' => ['Sage', 'sage@empy.test'],
            'EMP-014' => ['Noah', 'noah@example.com'],
            'EMP-015' => ['Craig', 'craig@example.com'],
        ];

        foreach ($employees as $nip => [$name, $email]) {
            DB::table('employees')->where('nip', $nip)->update([
                'name' => $name,
                'email' => $email,
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('users')->where('email', 'noah@example.com')->exists()) {
            $noahId = DB::table('users')->insertGetId([
                'name' => 'Noah',
                'email' => 'noah@example.com',
                'password' => Hash::make('kai250510'),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employees')->where('nip', 'EMP-014')->update([
                'user_id' => $noahId,
                'updated_at' => now(),
            ]);
        }

        $criteria = [
            'Kedisiplinan' => ['Discipline', 'Attendance, punctuality, and compliance with company policy.'],
            'Kualitas Kerja' => ['Work Quality', 'Accuracy, care, and quality of work output.'],
            'Kerjasama Tim' => ['Teamwork', 'Collaboration and communication with colleagues.'],
            'Inisiatif' => ['Initiative', 'Creativity and willingness to take initiative.'],
            'Tanggung Jawab' => ['Accountability', 'Consistency in delivering tasks on target.'],
        ];

        foreach ($criteria as $oldName => [$name, $description]) {
            DB::table('criteria')->where('name', $oldName)->update([
                'name' => $name,
                'description' => $description,
                'updated_at' => now(),
            ]);
        }

        $periods = [
            'Semester 2 2025' => 'H2 2025',
            'Triwulan 1 2026' => 'Q1 2026',
            'Triwulan 2 2026' => 'Q2 2026',
            'Evaluasi Tahunan 2026' => 'Annual Review 2026',
        ];

        foreach ($periods as $oldName => $name) {
            DB::table('evaluation_periods')->where('name', $oldName)->update([
                'name' => $name,
                'updated_at' => now(),
            ]);
        }

        DB::table('evaluations')
            ->where('notes', 'Evaluasi periodik berdasarkan capaian dan perilaku kerja.')
            ->update(['notes' => 'Periodic review based on delivery and workplace behavior.', 'updated_at' => now()]);

        $goals = [
            'Penyelesaian laporan operasional bulanan' => 'Complete monthly operations reports',
            'Tingkat kepuasan pelanggan' => 'Customer satisfaction rate',
            'Ketepatan waktu pengiriman' => 'On-time delivery rate',
        ];

        foreach ($goals as $oldTitle => $title) {
            DB::table('employee_goals')->where('title', $oldTitle)->update([
                'title' => $title,
                'unit' => $oldTitle === 'Penyelesaian laporan operasional bulanan' ? 'reports' : DB::raw('unit'),
                'notes' => $oldTitle === 'Penyelesaian laporan operasional bulanan' ? 'Consistently on time.' : DB::raw('notes'),
                'updated_at' => now(),
            ]);
        }

        DB::table('improvement_plans')
            ->where('title', 'Rencana Perbaikan Kinerja')
            ->update([
                'title' => 'Performance Improvement Plan',
                'actions' => 'Weekly coaching with the manager, discipline training, and a monthly progress review.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // The demo data rename is intentionally not reversed.
    }
};
