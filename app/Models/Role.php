<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Role extends Model
{
    protected $fillable = ['name', 'label', 'label_id', 'base', 'is_system', 'show_in_accounts'];

    protected $casts = [
        'is_system' => 'boolean',
        'show_in_accounts' => 'boolean',
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

    /**
     * Names of roles that have manager-level capabilities.
     *
     * @return list<string>
     */
    public static function managerNames(): array
    {
        return static::allCached()
            ->where('base', User::ROLE_MANAGER)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Roles available when assigning an account role to an employee/user.
     *
     * @return Collection<int, Role>
     */
    public static function accountRoles(?string $include = null): Collection
    {
        return static::query()
            ->where('name', '!=', User::ROLE_SUPER_ADMIN)
            ->where(function ($query) use ($include) {
                $query->where('show_in_accounts', true);

                if ($include) {
                    $query->orWhere('name', $include);
                }
            })
            ->orderBy('id')
            ->get();
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
