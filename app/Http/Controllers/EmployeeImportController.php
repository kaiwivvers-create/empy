<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportController extends Controller
{
    private const HEADER = ['nip', 'name', 'email', 'phone', 'role', 'password', 'department_code', 'position_name', 'manager_nip', 'hire_date', 'status'];

    /**
     * Header spellings the importer understands, mapped to internal columns.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'nip' => 'nip',
        'employee_id' => 'nip',
        'employee_no' => 'nip',
        'employee_number' => 'nip',
        'id_karyawan' => 'nip',
        'name' => 'name',
        'full_name' => 'name',
        'employee_name' => 'name',
        'nama' => 'name',
        'nama_karyawan' => 'name',
        'email' => 'email',
        'email_address' => 'email',
        'account_email' => 'email',
        'alamat_email' => 'email',
        'phone' => 'phone',
        'phone_number' => 'phone',
        'telephone' => 'phone',
        'telepon' => 'phone',
        'no_hp' => 'phone',
        'role' => 'role',
        'account_role' => 'role',
        'user_role' => 'role',
        'peran' => 'role',
        'password' => 'password',
        'account_password' => 'password',
        'kata_sandi' => 'password',
        'department' => 'department',
        'department_code' => 'department',
        'department_name' => 'department',
        'dept' => 'department',
        'departemen' => 'department',
        'position' => 'position',
        'position_name' => 'position',
        'jabatan' => 'position',
        'manager' => 'manager',
        'manager_nip' => 'manager',
        'manager_name' => 'manager',
        'atasan' => 'manager',
        'hire_date' => 'hire_date',
        'start_date' => 'hire_date',
        'join_date' => 'hire_date',
        'tanggal_masuk' => 'hire_date',
        'status' => 'status',
    ];

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::HEADER);
            fputcsv($out, ['EMP-100', 'Sample Employee', 'employee@company.com', '0812-0000-0000', 'employee', 'password', 'OPS', 'Staff', 'EMP-002', '2026-01-15', 'active']);
            fclose($out);
        }, 'template-import-karyawan.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('file')->getRealPath();
        $delimiter = $this->detectDelimiter($path);
        $handle = fopen($path, 'r');

        if (! $handle) {
            return redirect()->route('employees.index', ['open' => 'import'])
                ->with('error', __('app.import.unreadable'));
        }

        $header = $this->readHeader($handle, $delimiter);

        if (! $header || ! in_array('nip', $header, true) || ! in_array('name', $header, true)) {
            fclose($handle);

            return redirect()->route('employees.index', ['open' => 'import'])
                ->with('error', __('app.import.missing_header'));
        }

        $imported = 0;
        $skipped = 0;
        $accounts = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $line = [];
            foreach ($header as $index => $column) {
                $line[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            $nip = $line['nip'] ?? '';
            $name = $line['name'] ?? '';

            if ($nip === '' || $name === '') {
                $errors[] = __('app.import.row_error', ['row' => $rowNumber, 'reason' => __('app.import.nip_name_required')]);
                continue;
            }

            try {
                $result = $this->importRow($line, $nip, $name);
            } catch (ValidationException $exception) {
                $errors[] = __('app.import.row_error', [
                    'row' => $rowNumber,
                    'reason' => collect($exception->errors())->flatten()->first() ?: __('app.import.unreadable'),
                ]);
                continue;
            } catch (\Throwable) {
                $errors[] = __('app.import.row_error', ['row' => $rowNumber, 'reason' => __('app.import.unreadable')]);
                continue;
            }

            if ($result['skipped']) {
                $skipped++;
                continue;
            }

            $imported++;

            if ($result['account_created']) {
                $accounts++;
            }
        }

        fclose($handle);

        ActivityLogger::record('imported', "Employee CSV imported: {$imported} created, {$accounts} accounts, {$skipped} skipped");

        return redirect()->route('employees.index', ['open' => 'import'])
            ->with('success', __('app.import.success', [
                'imported' => $imported,
                'accounts' => $accounts,
                'skipped' => $skipped,
            ]))
            ->with('import_errors', $errors);
    }

    /**
     * @param  array<string, string>  $line
     * @return array{skipped: bool, account_created: bool}
     */
    private function importRow(array $line, string $nip, string $name): array
    {
        if (Employee::withTrashed()->where('nip', $nip)->exists()) {
            return ['skipped' => true, 'account_created' => false];
        }

        $department = $this->resolveDepartment($line['department'] ?? '');
        $position = $this->resolvePosition($line['position'] ?? '');
        $manager = $this->resolveManager($line['manager'] ?? '');
        $role = $this->resolveRole($line['role'] ?? '');
        $email = Str::lower(trim((string) ($line['email'] ?? '')));
        $password = trim((string) ($line['password'] ?? ''));

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => __('app.import.invalid_email', ['email' => $email]),
            ]);
        }

        if ($email !== '') {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                if ($existing->isSuperAdmin()) {
                    throw ValidationException::withMessages([
                        'email' => __('app.import.account_taken', ['email' => $email]),
                    ]);
                }

                if (Employee::withTrashed()->where('user_id', $existing->id)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => __('app.import.employee_taken', ['email' => $email]),
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($line, $nip, $name, $department, $position, $manager, $role, $email, $password) {
            $user = null;
            $accountCreated = false;

            if ($email !== '') {
                $user = User::where('email', $email)->first();

                if (! $user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'role' => $role,
                        'is_active' => true,
                        'password' => $password !== '' ? $password : 'password',
                    ]);

                    $accountCreated = true;
                }
            }

            $created = Employee::create([
                'user_id' => $user?->id,
                'nip' => $nip,
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'phone' => filled($line['phone'] ?? null) ? $line['phone'] : null,
                'department_id' => $department?->id,
                'position_id' => $position?->id,
                'manager_id' => $manager?->id,
                'hire_date' => $this->parseDate($line['hire_date'] ?? ''),
                'status' => $this->normalizeStatus($line['status'] ?? ''),
            ]);

            Organization::syncManagerWithDepartmentHead($created);

            return ['skipped' => false, 'account_created' => $accountCreated];
        });
    }

    /**
     * @param  resource  $handle
     * @return array<int, string>|null
     */
    private function readHeader($handle, string $delimiter): ?array
    {
        $first = fgetcsv($handle, 0, $delimiter);

        if ($first === false) {
            return null;
        }

        // Excel often prefixes the first cell with a UTF-8 BOM.
        $first[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($first[0] ?? ''));

        $header = [];

        foreach ($first as $index => $value) {
            $key = Str::slug(trim((string) $value), '_');
            $mapped = self::ALIASES[$key] ?? null;

            if ($mapped && ! in_array($mapped, $header, true)) {
                $header[$index] = $mapped;
            }
        }

        return $header;
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return ',';
        }

        $firstLine = (string) fgets($handle);
        fclose($handle);

        $commas = substr_count($firstLine, ',');
        $semicolons = substr_count($firstLine, ';');
        $tabs = substr_count($firstLine, "\t");

        if ($tabs > $commas && $tabs >= $semicolons) {
            return "\t";
        }

        return $semicolons > $commas ? ';' : ',';
    }

    private function resolveDepartment(string $value): ?Department
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $department = Department::where('code', $value)->first()
            ?? Department::whereRaw('LOWER(name) = ?', [Str::lower($value)])->first();

        if (! $department) {
            throw ValidationException::withMessages([
                'department' => __('app.import.department_not_found', ['code' => $value]),
            ]);
        }

        return $department;
    }

    private function resolvePosition(string $value): ?Position
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $position = Position::where('name', $value)->first()
            ?? Position::whereRaw('LOWER(name) = ?', [Str::lower($value)])->first();

        if (! $position) {
            throw ValidationException::withMessages([
                'position' => __('app.import.position_not_found', ['name' => $value]),
            ]);
        }

        return $position;
    }

    private function resolveManager(string $value): ?Employee
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $manager = Employee::where('nip', $value)->first()
            ?? Employee::whereRaw('LOWER(name) = ?', [Str::lower($value)])->first();

        if (! $manager) {
            throw ValidationException::withMessages([
                'manager' => __('app.import.manager_not_found', ['nip' => $value]),
            ]);
        }

        return $manager;
    }

    private function resolveRole(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return User::ROLE_EMPLOYEE;
        }

        $roles = Role::accountRoles();

        $role = $roles->firstWhere('name', $value)
            ?? $roles->first(fn (Role $candidate) => Str::lower($candidate->displayLabel()) === Str::lower($value))
            ?? $roles->first(fn (Role $candidate) => Str::slug($candidate->displayLabel(), '_') === Str::slug($value, '_'));

        if (! $role) {
            throw ValidationException::withMessages([
                'role' => __('app.import.role_not_found', ['role' => $value]),
            ]);
        }

        return $role->name;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function normalizeStatus(string $value): string
    {
        $value = Str::lower(trim($value));

        return in_array($value, ['inactive', 'nonaktif', 'non-active', '0', 'false', 'no', 'tidak'], true)
            ? 'inactive'
            : 'active';
    }
}
