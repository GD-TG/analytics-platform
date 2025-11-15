<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'yandex_id',
        'avatar',
        'role',
        'is_active',
        'yandex_metrika_client_id',
        'yandex_metrika_client_secret',
        'yandex_direct_client_id',
        'yandex_direct_client_secret',
        'sync_interval_minutes',
        'sync_enabled',
        'encrypted_password',
    ];

    protected $hidden = [
        'password',
        'encrypted_password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }
}
