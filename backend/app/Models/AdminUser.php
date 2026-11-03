<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'avatar',
        'is_active', 'last_login_at', 'last_login_ip', 'role_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
        'password'      => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    /** No role (legacy / first admin) or a "super" role = full access. */
    public function isSuper(): bool
    {
        return !$this->role_id || (bool) $this->role?->is_super;
    }

    /** Can this admin open the given area (see App\Support\AdminAreas)? */
    public function canArea(string $area): bool
    {
        return $this->isSuper() || in_array($area, $this->role?->permissions ?? [], true);
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_active;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ? $this->avatar : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=006A4E&color=fff';
    }
}
