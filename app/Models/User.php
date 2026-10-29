<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_EMPLOYEE = 'employee';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Hard-coded capability level of the role. Custom roles inherit one.
     */
    public function baseRole(): string
    {
        return Role::baseFor((string) $this->role);
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->baseRole() === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->baseRole() === self::ROLE_MANAGER;
    }

    public function isEmployee(): bool
    {
        return $this->baseRole() === self::ROLE_EMPLOYEE;
    }

    public function roleLabel(): string
    {
        // The super admin role is hidden from everyone except super admins.
        if ($this->isSuperAdmin() && ! auth()->user()?->isSuperAdmin()) {
            return __('app.roles.admin');
        }

        return Role::allCached()->firstWhere('name', $this->role)?->displayLabel()
            ?? __('app.roles.'.$this->role);
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path) {
            return asset('storage/'.$this->avatar_path);
        }

        return $this->employee?->photoUrl();
    }

    public function avatarInitials(): string
    {
        $source = $this->employee?->name ?? $this->name;
        $parts = preg_split('/\s+/', trim($source)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: '?';
    }
}
