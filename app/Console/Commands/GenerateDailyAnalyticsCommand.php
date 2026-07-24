<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateDailyAnalyticsCommand extends Command
{
    protected $signature = 'analytics:generate-daily';
    protected $description = 'Pre-calculate and cache daily marketplace BI analytics';

    public function handle(AnalyticsService $analyticsService): void
    {
        $overview = $analyticsService->getDashboardOverview();
        Cache::put('daily_analytics_overview', $overview, now()->addDay());
        $this->info("Successfully generated and cached daily BI analytics.");
    }
}
