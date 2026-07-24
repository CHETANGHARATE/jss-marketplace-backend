<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active Account',
            self::INACTIVE => 'Inactive Account',
            self::BANNED => 'Suspended / Banned Account',
        };
    }
}
