<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public static function record(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $changes = null,
    ): void {
        if ($changes === null && $model) {
            $snapshot = static::clean($model);

            $changes = in_array($action, ['deleted', 'archived'], true)
                ? ['before' => $snapshot]
                : ['after' => $snapshot];
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? class_basename($model) : null,
            'model_id' => $model?->getKey(),
            'description' => Str::limit($description, 250),
            'changes' => $changes,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Build a before/after diff for an update. Capture the raw attributes
     * before calling update() and pass them here afterwards.
     *
     * @param  array<string, mixed>  $before
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public static function changes(Model $model, array $before): array
    {
        $before = static::clean($model, $before);
        $after = static::clean($model);

        $beforeDiff = [];
        $afterDiff = [];

        foreach ($after as $key => $value) {
            if (! array_key_exists($key, $before)) {
                continue;
            }

            $old = $before[$key];

            if (static::normalize($old) !== static::normalize($value)) {
                $beforeDiff[$key] = $old;
                $afterDiff[$key] = $value;
            }
        }

        return ['before' => $beforeDiff, 'after' => $afterDiff];
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    public static function clean(Model $model, ?array $attributes = null): array
    {
        $attributes ??= $model->getAttributes();

        $hidden = array_unique(array_merge(
            $model->getHidden(),
            ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'],
        ));

        return array_diff_key($attributes, array_flip($hidden));
    }

    private static function normalize(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
