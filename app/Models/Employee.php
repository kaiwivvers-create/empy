<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nip',
        'name',
        'email',
        'photo_path',
        'phone',
        'department_id',
        'position_id',
        'manager_id',
        'hire_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /** The department this employee leads, if any. */
    public function headedDepartment(): HasOne
    {
        return $this->hasOne(Department::class, 'head_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(EmployeeGoal::class);
    }

    public function improvementPlans(): HasMany
    {
        return $this->hasMany(ImprovementPlan::class);
    }

    public function photoUrl(): ?string
    {
        // Account avatars are a valid fallback for employees whose photo was
        // uploaded before the employee record existed.
        $path = $this->photo_path ?: $this->user?->avatar_path;

        return $path ? asset('storage/'.$path) : null;
    }

    protected static function booted(): void
    {
        // The super admin's employee row is private to the super admin.
        static::addGlobalScope('visible_to_viewer', function (Builder $builder) {
            $user = auth()->user();

            if (! $user || $user->isSuperAdmin()) {
                return;
            }

            $hiddenUserIds = User::query()
                ->where('role', User::ROLE_SUPER_ADMIN)
                ->select('id');

            $builder->where(function (Builder $query) use ($hiddenUserIds) {
                $query->whereNull('user_id')->orWhereNotIn('user_id', $hiddenUserIds);
            });
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Employees who are actually evaluated: admins and managers are excluded.
     */
    public function scopeEvaluable(Builder $query): Builder
    {
        $leadershipRoles = Role::allCached()
            ->filter(fn (Role $role) => in_array($role->base, [User::ROLE_ADMIN, User::ROLE_MANAGER], true))
            ->pluck('name')
            ->all();

        return $query->where(function (Builder $inner) use ($leadershipRoles) {
            $inner->whereNull('user_id')
                ->orWhereHas('user', fn (Builder $user) => $user->whereNotIn('role', $leadershipRoles));
        });
    }

    /** Employees this user is allowed to evaluate. */
    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $employeeId = $user->employee?->id;

        return $query->where('manager_id', $employeeId);
    }

    public function isManagedBy(User $user): bool
    {
        return $user->isAdmin() || ($user->employee && $this->manager_id === $user->employee->id);
    }

    /** Whether the linked account is a leadership (manager or admin) account. */
    public function isManagerAccount(): bool
    {
        $user = $this->user;

        return $user !== null && ($user->isManager() || $user->isAdmin());
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: '?';
    }
}
