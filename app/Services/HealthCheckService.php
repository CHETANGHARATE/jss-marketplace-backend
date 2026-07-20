<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckService
{
    /**
     * Perform system health diagnostics.
     */
    public function checkHealth(): array
    {
        $dbStatus = $this->checkDatabase();
        $cacheStatus = $this->checkCache();
        $storageStatus = $this->checkStorage();

        $isHealthy = $dbStatus['healthy'] && $cacheStatus['healthy'] && $storageStatus['healthy'];

        return [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $dbStatus,
                'cache' => $cacheStatus,
                'storage' => $storageStatus,
            ],
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['healthy' => true, 'message' => 'Connected to MySQL database.'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            Cache::put('health_check_test', true, 10);
            $val = Cache::get('health_check_test');
            return ['healthy' => $val === true, 'message' => 'Cache layer operating normally.'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    protected function checkStorage(): array
    {
        $freeSpaceBytes = disk_free_space(storage_path());
        $freeSpaceMb = round($freeSpaceBytes / (1024 * 1024), 2);

        return [
            'healthy' => $freeSpaceMb > 100, // Healthy if > 100 MB available
            'free_space_mb' => $freeSpaceMb,
        ];
    }
}
