<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Maintenance Jobs
|--------------------------------------------------------------------------
*/

Schedule::command('carts:clean-expired')->dailyAt('00:00');
Schedule::command('analytics:generate-daily')->dailyAt('01:00');
Schedule::command('cart:process-abandoned')->hourly();
Schedule::command('notifications:process-scheduled')->everyFiveMinutes();
