<?php

namespace App\Models;

use App\Mail\PasswordResetMail;
use App\Services\Mail\LocalizedMailer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'preferred_locale',
        'theme_preference',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->withPivot(['id', 'role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function oauthIdentities(): HasMany
    {
        return $this->hasMany(UserOauthIdentity::class);
    }

    public function mfaSettings(): HasOne
    {
        return $this->hasOne(UserMfaSettings::class);
    }

    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }

    public function mfaRecoveryCodes(): HasMany
    {
        return $this->hasMany(MfaRecoveryCode::class);
    }

    public function mfaEnabled(): bool
    {
        if (! $this->relationLoaded('mfaSettings')) {
            $this->load('mfaSettings');
        }

        return (bool) $this->mfaSettings?->enabled;
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $email = urlencode($this->email);
        $resetUrl = rtrim((string) config('app.url'), '/').'/console/reset-password?token='.$token.'&email='.$email;
        $expires = (int) config('auth.passwords.users.expire', 60);

        app(LocalizedMailer::class)->sendToUser(
            $this,
            new PasswordResetMail($this, $resetUrl, $expires),
        );
    }
}
