<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerFeedbackScore extends Model
{
    protected $fillable = [
        'peer_feedback_id',
        'criteria_id',
        'score',
        'comment',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(PeerFeedback::class, 'peer_feedback_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criteria_id');
    }
}
