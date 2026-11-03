<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_ON_HOLD, self::STATUS_COMPLETED];
    }

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'due_date',
        'owner_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_members')->withTimestamps();
    }

    /**
     * Admins see every project, managers the ones they own, employees only the
     * projects they have been added to.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $employeeId = $user->employee?->id;

        if (! $user->isManager() && ! $employeeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($user, $employeeId) {
            if ($user->isManager()) {
                $query->where('owner_id', $user->id);
            }

            if ($employeeId) {
                $query->orWhereHas('members', fn (Builder $members) => $members->whereKey($employeeId));
            }
        });
    }

    public function isVisibleTo(User $user): bool
    {
        return static::query()->visibleTo($user)->whereKey($this->getKey())->exists();
    }

    public function canBeManagedBy(User $user): bool
    {
        return $user->isAdmin() || $this->owner_id === $user->id;
    }

    public function isMember(Employee $employee): bool
    {
        return $this->members()->whereKey($employee->getKey())->exists();
    }

    public function statusLabel(): string
    {
        return __('app.projects.statuses.'.$this->status);
    }
}
