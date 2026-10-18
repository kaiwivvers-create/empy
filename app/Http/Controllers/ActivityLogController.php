<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $isSuperAdmin = $request->user()->isSuperAdmin();

        $logs = ActivityLog::with('user')
            ->when(! $isSuperAdmin, function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNull('user_id')
                        ->orWhereHas('user', fn ($user) => $user->where('role', '!=', User::ROLE_SUPER_ADMIN));
                });
            })
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->integer('user')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('description', 'like', '%'.$request->string('search').'%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('activity.index', [
            'logs' => $logs,
            'users' => User::query()
                ->when(! $isSuperAdmin, fn ($query) => $query->where('role', '!=', User::ROLE_SUPER_ADMIN))
                ->orderBy('name')
                ->get(),
            'actions' => ActivityLog::query()
                ->when(! $isSuperAdmin, function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereNull('user_id')
                            ->orWhereHas('user', fn ($user) => $user->where('role', '!=', User::ROLE_SUPER_ADMIN));
                    });
                })
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'filters' => $request->only('action', 'user', 'search'),
        ]);
    }

    public function revert(Request $request, ActivityLog $log): RedirectResponse
    {
        $this->authorizeLog($request, $log);

        if ($log->isReverted()) {
            return back()->with('error', __('app.activity.already_reverted'));
        }

        $model = $this->resolveModel($log);

        try {
            match ($log->action) {
                'deleted' => $this->restoreDeleted($log, $model),
                'created' => $this->undoCreation($model),
                'updated', 'activated', 'closed' => $this->restorePreviousValues($log, $model),
                default => throw new \RuntimeException(__('app.activity.not_revertable')),
            };
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('app.activity.revert_failed'));
        }

        $log->update(['reverted_at' => now()]);

        ActivityLogger::record('reverted', 'Reverted: '.$log->description);

        return back()->with('success', __('app.activity.success_reverted'));
    }

    public function destroy(Request $request, ActivityLog $log): RedirectResponse
    {
        $this->authorizeLog($request, $log);

        if ($log->isReverted()) {
            return back()->with('error', __('app.activity.already_reverted'));
        }

        try {
            $model = $this->resolveModel($log);

            if ($model && $log->action === 'deleted' && $this->usesSoftDeletes($model)) {
                $model->forceDelete();
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('app.activity.purge_failed'));
        }

        $log->delete();

        return back()->with('success', __('app.activity.success_purged'));
    }

    private function authorizeLog(Request $request, ActivityLog $log): void
    {
        if ($log->user?->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(404);
        }
    }

    private function resolveModel(ActivityLog $log): ?Model
    {
        if (! $log->model_type || ! $log->model_id) {
            return null;
        }

        $class = 'App\\Models\\'.$log->model_type;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        $query = $class::query();

        if ($this->usesSoftDeletes($class)) {
            $query->withTrashed();
        }

        return $query->find($log->model_id);
    }

    private function usesSoftDeletes(object|string $model): bool
    {
        $class = is_object($model) ? $model::class : $model;

        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    private function restoreDeleted(ActivityLog $log, ?Model $model): void
    {
        if ($model) {
            if ($this->usesSoftDeletes($model) && method_exists($model, 'trashed') && $model->trashed()) {
                $model->restore();

                return;
            }

            throw new \RuntimeException('Record is already active.');
        }

        $this->recreateFromSnapshot($log);
    }

    private function undoCreation(?Model $model): void
    {
        if (! $model) {
            return;
        }

        $model->delete();
    }

    private function restorePreviousValues(ActivityLog $log, ?Model $model): void
    {
        if (! $model) {
            throw new \RuntimeException('Record no longer exists.');
        }

        $attributes = $log->before();

        if ($attributes === []) {
            throw new \RuntimeException('No previous values recorded.');
        }

        $model->forceFill($attributes)->save();
    }

    private function recreateFromSnapshot(ActivityLog $log): void
    {
        $class = 'App\\Models\\'.$log->model_type;
        $attributes = $log->before();

        if ($attributes === []) {
            throw new \RuntimeException('No snapshot available.');
        }

        $model = new $class;
        $model->forceFill($attributes);

        if ($model instanceof User && ! array_key_exists('password', $attributes)) {
            $model->forceFill(['password' => Hash::make(Str::random(40))]);
        }

        $model->save();
    }
}
