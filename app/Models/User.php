<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
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
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        $permissions = $this->role?->permissions ?? [];

        return $this->role?->code === 'super-admin' || in_array('*', $permissions, true);
    }

    public function canAccessModule(string $module): bool
    {
        $permissions = $this->role?->permissions ?? [];

        return in_array('*', $permissions, true) || in_array($module, $permissions, true);
    }

    public function canPerform(string $permission, ?string $fallbackModule = null): bool
    {
        $permissions = $this->role?->permissions ?? [];
        return $this->isSuperAdmin() || in_array($permission, $permissions, true) || ($fallbackModule && in_array($fallbackModule, $permissions, true));
    }
}
