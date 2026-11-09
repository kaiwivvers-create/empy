<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeGoal extends Model
{
    public const UNITS = [
        '%',
        'units',
        'items',
        'tasks',
        'deals',
        'customers',
        'projects',
        'reports',
        'hours',
        'days',
        'points',
        'currency',
    ];

    public const INTEGER_UNITS = [
        'units',
        'items',
        'tasks',
        'deals',
        'customers',
        'projects',
        'reports',
        'days',
    ];

    protected $fillable = [
        'employee_id',
        'period_id',
        'title',
        'target_value',
        'unit',
        'achievement_value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'achievement_value' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'period_id');
    }

    public function achievementPercent(): ?float
    {
        if ((float) $this->target_value <= 0 || $this->achievement_value === null) {
            return null;
        }

        return round((float) $this->achievement_value / (float) $this->target_value * 100, 1);
    }

    public static function unitAllowsDecimals(?string $unit): bool
    {
        return ! in_array($unit, self::INTEGER_UNITS, true);
    }
}
