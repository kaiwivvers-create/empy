<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'changes',
        'reverted_at',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'reverted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function before(): array
    {
        $changes = $this->getAttribute('changes');

        return (array) ($changes['before'] ?? []);
    }

    public function after(): array
    {
        $changes = $this->getAttribute('changes');

        return (array) ($changes['after'] ?? []);
    }

    public function isReverted(): bool
    {
        return $this->reverted_at !== null;
    }

    public function canRevert(): bool
    {
        if ($this->isReverted() || ! $this->model_type || ! $this->model_id) {
            return false;
        }

        return match ($this->action) {
            'created' => true,
            'updated', 'activated', 'closed' => $this->before() !== [],
            'deleted' => $this->before() !== [],
            default => false,
        };
    }

    public function canPurge(): bool
    {
        return ! $this->isReverted() && $this->action === 'deleted';
    }
}
