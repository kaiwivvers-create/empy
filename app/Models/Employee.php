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
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
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
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: '?';
    }
}
