<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    protected HealthCheckService $healthService;

    public function __construct(HealthCheckService $healthService)
    {
        $this->healthService = $healthService;
    }

    /**
     * Public Health Check Diagnostic Endpoint.
     */
    public function check(): JsonResponse
    {
        $result = $this->healthService->checkHealth();
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $statusCode);
    }
}
