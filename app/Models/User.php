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
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

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
     * Safely assign a role, ensuring it exists across guards (sanctum, web, api).
     * Prevents RoleDoesNotExist runtime exceptions.
     */
    public function assignRoleSafely(string $roleName): void
    {
        // 1. Update the database column 'role' on users table
        $enumVal = UserRole::tryFrom($roleName);
        if ($enumVal) {
            $this->update(['role' => $enumVal]);
        }

        // 2. Ensure Spatie roles exist for all active guards
        $guards = ['sanctum', 'web', 'api'];
        foreach ($guards as $guard) {
            try {
                Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => $guard,
                ]);
            } catch (\Throwable $e) {
                // Ignore race conditions or duplicate entries
            }
        }

        // 3. Assign role to user model
        try {
            if (!$this->hasRoleSafely($roleName)) {
                $this->assignRole($roleName);
            }
        } catch (\Throwable $e) {
            Log::warning("assignRoleSafely notice for role '{$roleName}': " . $e->getMessage());
        }
    }

    /**
     * Safely check if user has a role without throwing guard exceptions.
     */
    public function hasRoleSafely(string $roleName): bool
    {
        if ($this->role instanceof UserRole && $this->role->value === $roleName) {
            return true;
        }
        if (is_string($this->role) && $this->role === $roleName) {
            return true;
        }
        try {
            return $this->hasRole($roleName);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Helper to check if user is an Administrator
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN || $this->hasRoleSafely(UserRole::ADMIN->value);
    }

    /**
     * Helper to check if user is a Vendor / Seller
     */
    public function isSeller(): bool
    {
        return $this->role === UserRole::SELLER || $this->hasRoleSafely(UserRole::SELLER->value);
    }

    /**
     * Helper to check if user is a Customer
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER || $this->hasRoleSafely(UserRole::CUSTOMER->value);
    }

    /**
     * Orders placed by this user
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Addresses saved by this user
     */
    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    /**
     * Vendor store if seller
     */
    public function vendorStore(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VendorStore::class, 'user_id');
    }
}
