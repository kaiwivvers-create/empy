<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    public const LEVELS = [
        'Staff',
        'Junior',
        'Senior',
        'Manager',
        'Head Manager',
        'Admin',
        'Super Admin',
    ];

    protected $fillable = ['name', 'level', 'is_visible'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
