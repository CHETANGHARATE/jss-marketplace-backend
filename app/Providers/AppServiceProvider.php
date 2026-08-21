<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Event;
use App\Events\PriceChanged;
use App\Events\ProductRestocked;
use App\Events\ProductLaunched;
use App\Events\StoreProductCreated;
use App\Events\InventoryLow;
use App\Listeners\NotifyPriceDropListener;
use App\Listeners\NotifyBackInStockListener;
use App\Listeners\NotifyProductLaunchListener;
use App\Listeners\NotifyStoreFollowersListener;
use App\Listeners\NotifyLowStockListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PriceChanged::class, NotifyPriceDropListener::class);
        Event::listen(ProductRestocked::class, NotifyBackInStockListener::class);
        Event::listen(ProductLaunched::class, NotifyProductLaunchListener::class);
        Event::listen(StoreProductCreated::class, NotifyStoreFollowersListener::class);
        Event::listen(InventoryLow::class, NotifyLowStockListener::class);
    }
}
