<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isCollector(): bool
    {
        return $this->role === Role::Collector;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'collector_id');
    }

    public function collectionVisits(): HasMany
    {
        return $this->hasMany(CollectionVisit::class, 'collector_id');
    }

    public function cashSettlements(): HasMany
    {
        return $this->hasMany(CashSettlement::class, 'collector_id');
    }

    public function confirmedSettlements(): HasMany
    {
        return $this->hasMany(CashSettlement::class, 'confirmed_by');
    }
}
