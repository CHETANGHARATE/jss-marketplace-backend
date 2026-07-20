<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Resolve the requested payment gateway driver.
     */
    public function driver(string $gateway = 'razorpay'): PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            'razorpay' => new RazorpayGateway(),
            'stripe' => new StripeGateway(),
            default => throw new InvalidArgumentException("Unsupported payment gateway driver [{$gateway}]."),
        };
    }
}
