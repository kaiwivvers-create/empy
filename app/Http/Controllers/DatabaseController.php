<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class DatabaseController extends Controller
{
    private const BACKUP_DIR = 'backups';

    public function index(): View
    {
        $tables = $this->tableNames();
        $counts = [];

        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        $backups = collect(Storage::disk('local')->files(self::BACKUP_DIR))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'path' => $path,
                'size' => Storage::disk('local')->size($path),
                'modified' => Storage::disk('local')->lastModified($path),
            ])
            ->sortByDesc('modified')
            ->values();

        $databaseFile = config('database.connections.'.config('database.default').'.database');

        return view('settings.database', [
            'driver' => DB::connection()->getDriverName(),
            'database' => is_string($databaseFile) ? basename($databaseFile) : config('database.default'),
            'tables' => $tables,
            'counts' => $counts,
            'backups' => $backups,
            'totalRows' => array_sum($counts),
        ]);
    }

    public function export(): Response
    {
        $filename = 'empy-backup-'.now()->format('Ymd-His').'.sql';

        return response($this->dump(), 200, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'sql_file' => ['required', 'file', 'max:51200'],
            'confirm_restore' => ['accepted'],
        ]);

        $file = $request->file('sql_file');
        $sql = trim((string) file_get_contents($file->getRealPath()));

        if ($sql === '') {
            return back()->with('error', __('app.database.empty_file'));
        }

        $backup = $this->storeBackup();

        try {
            $statements = $this->splitStatements($sql);
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

            // SQLite DDL is transactional; MySQL commits DDL implicitly, so a
            // wrapper transaction would be left in an inconsistent state.
            if ($isSqlite) {
                DB::statement('PRAGMA foreign_keys = OFF');
                DB::beginTransaction();
            }

            foreach ($statements as $statement) {
                $statement = trim($statement);

                if ($statement === '') {
                    continue;
                }

                DB::unprepared($statement);
            }

            if ($isSqlite) {
                DB::commit();
                DB::statement('PRAGMA foreign_keys = ON');
            }
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            try {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON');
                }
            } catch (Throwable) {
                // Driver does not support the pragma.
            }

            report($exception);

            return back()->with('error', __('app.database.import_failed', [
                'message' => Str::limit($exception->getMessage(), 180),
                'backup' => $backup,
            ]));
        }

        ActivityLogger::record('updated', 'Database restored from '.$file->getClientOriginalName());

        return back()->with('success', __('app.database.import_success', ['backup' => $backup]));
    }

    public function download(string $backup): Response
    {
        $path = self::BACKUP_DIR.'/'.basename($backup);

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.basename($backup).'"',
        ]);
    }

    public function destroy(string $backup): RedirectResponse
    {
        $path = self::BACKUP_DIR.'/'.basename($backup);

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', __('app.database.backup_deleted'));
    }

    /**
     * @return list<string>
     */
    private function tableNames(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))
                ->pluck('name')
                ->sort()
                ->values()
                ->all();
        }

        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => (array) $row)
            ->map(fn (array $row) => reset($row))
            ->sort()
            ->values()
            ->all();
    }

    private function dump(): string
    {
        $driver = DB::connection()->getDriverName();
        $lines = [
            '-- Empy database backup',
            '-- Generated: '.now()->toDateTimeString(),
            '-- Driver: '.$driver,
            '',
        ];

        if ($driver !== 'sqlite') {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
            $lines[] = '';
        }

        foreach ($this->tableNames() as $table) {
            $create = $this->createStatement($table);

            $lines[] = "-- Table: {$table}";

            if ($driver === 'sqlite') {
                $lines[] = 'DROP TABLE IF EXISTS "'.$table.'";';
            } else {
                $lines[] = 'DROP TABLE IF EXISTS `'.$table.'`;';
            }

            if ($create) {
                $lines[] = rtrim($create, ';').';';
            }

            $rows = DB::table($table)->cursor();
            $wrapped = $driver === 'sqlite' ? '"'.$table.'"' : '`'.$table.'`';
            $pdo = DB::connection()->getPdo();

            foreach ($rows as $row) {
                $data = (array) $row;
                $columns = array_map(
                    fn (string $column) => $driver === 'sqlite' ? '"'.$column.'"' : '`'.$column.'`',
                    array_keys($data),
                );

                $values = array_map(
                    fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    array_values($data),
                );

                $lines[] = 'INSERT INTO '.$wrapped.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).');';
            }

            $lines[] = '';
        }

        if ($driver !== 'sqlite') {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        }

        return implode("\n", $lines);
    }

    private function createStatement(string $table): ?string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return DB::table('sqlite_master')->where('type', 'table')->where('name', $table)->value('sql');
        }

        $row = (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`');

        return $row['Create Table'] ?? null;
    }

    private function storeBackup(): string
    {
        $name = 'pre-restore-'.now()->format('Ymd-His').'.sql';

        Storage::disk('local')->put(self::BACKUP_DIR.'/'.$name, $this->dump());

        return $name;
    }

    /**
     * Split a SQL dump into single statements while respecting quoted strings.
     *
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $length = strlen($sql);
        $quote = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($quote !== null) {
                $current .= $char;

                if ($char === '\\' && $quote !== '`') {
                    if ($next !== '') {
                        $current .= $next;
                        $i++;
                    }

                    continue;
                }

                if ($char === $quote) {
                    if ($next === $quote) {
                        $current .= $next;
                        $i++;

                        continue;
                    }

                    $quote = null;
                }

                continue;
            }

            if ($char === '-' && $next === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }

                continue;
            }

            if ($char === '#' && trim($current) === '') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }

                continue;
            }

            if ($char === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);

                if ($end === false) {
                    break;
                }

                $i = $end + 1;

                continue;
            }

            if (in_array($char, ["'", '"', '`'], true)) {
                $quote = $char;
                $current .= $char;

                continue;
            }

            if ($char === ';') {
                if (trim($current) !== '') {
                    $statements[] = $current;
                }

                $current = '';

                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}
