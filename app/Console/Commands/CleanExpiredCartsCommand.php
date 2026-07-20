<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class CleanExpiredCartsCommand extends Command
{
    protected $signature = 'carts:clean-expired';
    protected $description = 'Clean up abandoned shopping carts older than 30 days';

    public function handle(): void
    {
        $count = Cart::where('updated_at', '<', now()->subDays(30))->delete();
        $this->info("Cleaned up {$count} abandoned carts older than 30 days.");
    }
}
