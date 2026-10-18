<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportController extends Controller
{
    private const HEADER = ['nip', 'name', 'email', 'phone', 'department_code', 'position_name', 'manager_nip', 'hire_date', 'status'];

    public function index(): View
    {
        return view('employees.import');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::HEADER);
            fputcsv($out, ['EMP-100', 'Sample Employee', 'employee@company.com', '0812-0000-0000', 'OPS', 'Staff', 'EMP-002', '2026-01-15', 'active']);
            fclose($out);
        }, 'template-import-karyawan.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if (! $handle) {
            return back()->with('error', __('app.import.unreadable'));
        }

        $header = array_map(fn ($value) => Str::slug(trim((string) $value), '_'), (array) fgetcsv($handle));

        if (array_diff(['nip', 'name'], $header)) {
            fclose($handle);

            return back()->with('error', __('app.import.missing_header'));
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $line = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            $line = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $line ?: []);

            $nip = $line['nip'] ?? null;
            $name = $line['name'] ?? null;

            if (! $nip || ! $name) {
                $errors[] = __('app.import.row_error', ['row' => $rowNumber, 'reason' => __('app.import.nip_name_required')]);
                continue;
            }

            if (Employee::withTrashed()->where('nip', $nip)->exists()) {
                $skipped++;
                continue;
            }

            $department = null;
            if (filled($line['department_code'] ?? null)) {
                $department = Department::where('code', $line['department_code'])->first();

                if (! $department) {
                    $errors[] = __('app.import.row_error', ['row' => $rowNumber, 'reason' => __('app.import.department_not_found', ['code' => $line['department_code']])]);
                    continue;
                }
            }

            $position = null;
            if (filled($line['position_name'] ?? null)) {
                $position = Position::where('name', $line['position_name'])->first();

                if (! $position) {
                    $errors[] = __('app.import.row_error', ['row' => $rowNumber, 'reason' => __('app.import.position_not_found', ['name' => $line['position_name']])]);
                    continue;
                }
            }

            $manager = null;
            if (filled($line['manager_nip'] ?? null)) {
                $manager = Employee::where('nip', $line['manager_nip'])->first();

                if (! $manager) {
                    $errors[] = __('app.import.row_error', ['row' => $rowNumber, 'reason' => __('app.import.manager_not_found', ['nip' => $line['manager_nip']])]);
                    continue;
                }
            }

            $hireDate = null;
            if (filled($line['hire_date'] ?? null)) {
                $timestamp = strtotime((string) $line['hire_date']);
                $hireDate = $timestamp ? date('Y-m-d', $timestamp) : null;
            }

            Employee::create([
                'nip' => $nip,
                'name' => $name,
                'email' => $line['email'] ?? null,
                'phone' => $line['phone'] ?? null,
                'department_id' => $department?->id,
                'position_id' => $position?->id,
                'manager_id' => $manager?->id,
                'hire_date' => $hireDate,
                'status' => in_array($line['status'] ?? 'active', ['active', 'inactive'], true) ? $line['status'] : 'active',
            ]);

            $imported++;
        }

        fclose($handle);

        ActivityLogger::record('imported', "Employee CSV imported: {$imported} created, {$skipped} skipped");

        return redirect()->route('employees.import')
            ->with('success', __('app.import.success', ['imported' => $imported, 'skipped' => $skipped]))
            ->with('import_errors', $errors);
    }
}
