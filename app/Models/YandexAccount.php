<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class YandexAccount extends Model
{
    use HasFactory;

    protected $table = 'yandex_accounts';

    protected $fillable = [
        'user_id',
        'provider_user_id',
        'counter_id',
        'encrypted_access_token',
        'encrypted_refresh_token',
        'scopes',
        'expires_at',
        'revoked'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    // Accessors / mutators for encrypted tokens
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['encrypted_access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute()
    {
        if (empty($this->attributes['encrypted_access_token'])) {
            return null;
        }
        try {
            return Crypt::decryptString($this->attributes['encrypted_access_token']);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['encrypted_refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute()
    {
        if (empty($this->attributes['encrypted_refresh_token'])) {
            return null;
        }
        try {
            return Crypt::decryptString($this->attributes['encrypted_refresh_token']);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setScopesAttribute($value)
    {
        $this->attributes['scopes'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getScopesAttribute($value)
    {
        if (!$value) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [$value];
    }
}
