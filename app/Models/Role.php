<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Role extends Model
{
    protected $fillable = ['name', 'label', 'label_id', 'base', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /** @var Collection<int, Role>|null */
    protected static ?Collection $cache = null;

    /**
     * Access levels used for hard-coded capabilities (routes, gates, views).
     *
     * @return list<string>
     */
    public static function bases(): array
    {
        return ['admin', 'manager', 'employee'];
    }

    /**
     * @return Collection<int, Role>
     */
    public static function allCached(): Collection
    {
        return static::$cache ??= static::query()->orderBy('id')->get();
    }

    public static function flushCache(): void
    {
        static::$cache = null;
    }

    public static function baseFor(string $name): string
    {
        $base = static::allCached()->firstWhere('name', $name)?->base;

        if (in_array($base, static::bases(), true)) {
            return $base;
        }

        return in_array($name, static::bases(), true) ? $name : 'employee';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    public function displayLabel(): string
    {
        if (app()->getLocale() === 'id' && filled($this->label_id)) {
            return $this->label_id;
        }

        if (filled($this->label)) {
            return $this->label;
        }

        return __('app.roles.'.$this->name);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
