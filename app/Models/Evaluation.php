<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'employee_id',
        'evaluator_id',
        'final_score',
        'manager_score',
        'status',
        'purpose',
        'notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'manager_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Never expose the super admin's evaluation history to lower roles.
        static::addGlobalScope('visible_to_viewer', function (Builder $builder) {
            $user = auth()->user();

            if (! $user || $user->isSuperAdmin()) {
                return;
            }

            $hiddenEmployeeIds = Employee::query()
                ->withoutGlobalScope('visible_to_viewer')
                ->whereIn('user_id', User::query()->where('role', User::ROLE_SUPER_ADMIN)->select('id'))
                ->select('id');

            $builder->whereNotIn('employee_id', $hiddenEmployeeIds);
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }

    public function peerFeedbacks(): HasMany
    {
        return $this->hasMany(PeerFeedback::class);
    }

    public function improvementPlans(): HasMany
    {
        return $this->hasMany(ImprovementPlan::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Employees may give feedback when invited, but only non-employee roles
     * (admins, managers, and custom leadership roles) may send requests.
     */
    public function userCanManageFeedback(?User $user): bool
    {
        if (! $user || $user->isEmployee()) {
            return false;
        }

        if ($user->isAdmin() || $this->evaluator_id === $user->id) {
            return true;
        }

        return $user->isManager()
            && $user->employee !== null
            && $this->employee?->manager_id === $user->employee->id;
    }

    /**
     * Weighted score on a 0-100 scale.
     */
    public function computeFinalScore(): float
    {
        $scores = $this->scores()->with('criterion')->get()
            ->filter(fn (EvaluationScore $score) => $score->criterion !== null);

        $totalWeight = (float) $scores->sum(fn (EvaluationScore $score) => (float) $score->criterion->weight);

        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weighted = $scores->sum(
            fn (EvaluationScore $score) => ((float) $score->score / 5) * (float) $score->criterion->weight
        );

        return round($weighted / $totalWeight * 100, 2);
    }

    /**
     * Average peer rating converted to a 0-100 scale, or null when no peer has replied.
     */
    public function peerScore(): ?float
    {
        $submitted = $this->peerFeedbacks->filter(fn (PeerFeedback $feedback) => $feedback->isSubmitted());

        if ($submitted->isEmpty()) {
            return null;
        }

        return round((float) $submitted->avg('score') / 5 * 100, 2);
    }

    /**
     * 360 score: 70% manager, 30% peer average once feedback exists.
     */
    public function recomputeFinalScore(): float
    {
        $managerScore = (float) ($this->manager_score ?? $this->final_score ?? 0);
        $peer = $this->peerScore();

        return $peer === null
            ? round($managerScore, 2)
            : round($managerScore * 0.7 + $peer * 0.3, 2);
    }

    public static function gradeFor(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }

    public function gradeLabel(): string
    {
        return __('app.grades.'.static::gradeFor((float) $this->final_score));
    }
}
