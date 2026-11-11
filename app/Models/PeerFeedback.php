<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeerFeedback extends Model
{
    protected $table = 'peer_feedbacks';

    protected $fillable = [
        'evaluation_id',
        'reviewer_id',
        'relationship',
        'score',
        'comment',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PeerFeedbackScore::class);
    }

    /**
     * Criteria-weighted peer rating on a 0-100 scale. Falls back to the single
     * overall score used before criteria-based peer feedback existed.
     */
    public function overallScore(): ?float
    {
        if (! $this->relationLoaded('scores')) {
            $this->load('scores.criterion');
        }

        $scores = $this->scores->filter(fn (PeerFeedbackScore $score) => $score->criterion !== null);
        $totalWeight = (float) $scores->sum(fn (PeerFeedbackScore $score) => (float) $score->criterion->weight);

        if ($totalWeight > 0) {
            $weighted = $scores->sum(
                fn (PeerFeedbackScore $score) => ((float) $score->score / 5) * (float) $score->criterion->weight
            );

            return round($weighted / $totalWeight * 100, 2);
        }

        return $this->score !== null ? round((float) $this->score / 5 * 100, 2) : null;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null
            && ($this->score !== null || $this->scores()->exists());
    }
}
