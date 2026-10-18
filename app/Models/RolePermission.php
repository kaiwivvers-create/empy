<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission', 'is_allowed'];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }
}
