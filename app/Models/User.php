<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Illuminate\Support\Str;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasName, HasTenants
{
    use Notifiable;

    /**
     * Get the user's name for Filament.
     *
     * @return string
     */
    public function getFilamentName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unnamed User';
    }

    /**
     * Get the user's name for Filament.
     *
     * @return string
     */
    public function getUserName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unnamed User';
    }

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'google_id',
        'email_verified_at',
        'contact_info'
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * @param UserRole[] $roles
     */
    public function isAllowedInRoles(array $roles): bool
    {
        return in_array(UserRole::tryFrom($this->role), $roles, strict: true);
    }

    public function isAdmin(): bool
    {
        return $this->role == UserRole::ADMIN->value;
    }

    public function isEO(): bool
    {
        return $this->role == UserRole::EVENT_ORGANIZER->value;
    }

    public function isVendor(): bool
    {
        return $this->role == UserRole::VENDOR->value;
    }

    public function isUser(): bool
    {
        return $this->role == UserRole::USER->value;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role == UserRole::ADMIN->value || $this->role == UserRole::VENDOR->value || $this->role == UserRole::EVENT_ORGANIZER->value;
    }

    // has tenant things

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_team', 'user_id', 'team_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }

    public function contactInfo(): BelongsTo
    {
        return $this->belongsTo(UserContact::class, 'contact_info', 'contact_id');
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant)->exists();
    }
}
