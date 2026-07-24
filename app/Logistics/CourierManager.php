<?php

namespace App\Logistics;

use App\Contracts\CourierDriverInterface;
use InvalidArgumentException;

class CourierManager
{
    /**
     * Resolve courier logistics driver by code.
     */
    public function driver(string $code = 'delhivery'): CourierDriverInterface
    {
        return match (strtolower($code)) {
            'delhivery', 'bluedart', 'shiprocket' => new DelhiveryDriver(),
            'local' => new LocalCourierDriver(),
            default => new LocalCourierDriver(),
        };
    }
}
