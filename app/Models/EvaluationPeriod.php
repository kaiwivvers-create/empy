<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'type', 'start_date', 'end_date', 'status'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'period_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Whether today (or the given day) falls inside the period's date range. */
    public function coversDate(?CarbonInterface $date = null): bool
    {
        $day = ($date ?? now())->toDateString();

        return $this->start_date->toDateString() <= $day
            && $this->end_date->toDateString() >= $day;
    }

    /**
     * Recorded data may still be corrected while the period is inside its
     * date range and has not been explicitly closed.
     */
    public function isOpenForEditing(): bool
    {
        return $this->status !== 'closed' && $this->coversDate();
    }

    /** New entries are only accepted while the period is active and in range. */
    public function acceptsEntries(): bool
    {
        return $this->status === 'active' && $this->coversDate();
    }
}
