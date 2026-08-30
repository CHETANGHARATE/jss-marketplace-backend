<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Enums\UserRole;
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
        // Implicitly grant 'super_admin' full unrestricted access to all permissions
        Gate::before(function ($user, $ability) {
            if ($user && ($user->hasRoleSafely('super_admin') || ($user->id === 1 && $user->role === UserRole::ADMIN))) {
                return true;
            }
        });

        Event::listen(PriceChanged::class, NotifyPriceDropListener::class);
        Event::listen(ProductRestocked::class, NotifyBackInStockListener::class);
        Event::listen(ProductLaunched::class, NotifyProductLaunchListener::class);
        Event::listen(StoreProductCreated::class, NotifyStoreFollowersListener::class);
        Event::listen(InventoryLow::class, NotifyLowStockListener::class);
    }
}
