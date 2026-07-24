<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Helper to check if user is an Administrator
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN || $this->hasRole(UserRole::ADMIN->value);
    }

    /**
     * Helper to check if user is a Vendor / Seller
     */
    public function isSeller(): bool
    {
        return $this->role === UserRole::SELLER || $this->hasRole(UserRole::SELLER->value);
    }

    /**
     * Helper to check if user is a Customer
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER || $this->hasRole(UserRole::CUSTOMER->value);
    }
}
