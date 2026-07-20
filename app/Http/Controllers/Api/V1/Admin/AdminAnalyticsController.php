<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsDateRangeRequest;
use App\Http\Requests\ReportExportRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\AuditLogResource;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Services\AnalyticsService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;
    protected ReportExportService $exportService;

    public function __construct(AnalyticsService $analyticsService, ReportExportService $exportService)
    {
        $this->analyticsService = $analyticsService;
        $this->exportService = $exportService;
    }

    /**
     * Get Admin Dashboard Overview stats & sales chart.
     */
    public function overview(): JsonResponse
    {
        $data = $this->analyticsService->getDashboardOverview();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Get Sales BI analytics.
     */
    public function sales(AnalyticsDateRangeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data = $this->analyticsService->getSalesAnalytics($validated['start_date'] ?? null, $validated['end_date'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Get Customer analytics.
     */
    public function customers(): JsonResponse
    {
        $data = $this->analyticsService->getCustomerAnalytics();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Get Inventory health analytics.
     */
    public function inventory(): JsonResponse
    {
        $data = $this->analyticsService->getInventoryAnalytics();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Export Sales Report (CSV/JSON).
     */
    public function exportSales(ReportExportRequest $request)
    {
        $validated = $request->validated();
        $format = $validated['format'] ?? 'csv';

        $report = $this->exportService->exportSalesReport($format, $validated['start_date'] ?? null, $validated['end_date'] ?? null);

        if ($format === 'json') {
            return response()->json(['success' => true, 'data' => $report], 200);
        }

        return response($report, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_report.csv"',
        ]);
    }

    /**
     * Export Inventory Report (CSV/JSON).
     */
    public function exportInventory(ReportExportRequest $request)
    {
        $validated = $request->validated();
        $format = $validated['format'] ?? 'csv';

        $report = $this->exportService->exportInventoryReport($format);

        if ($format === 'json') {
            return response()->json(['success' => true, 'data' => $report], 200);
        }

        return response($report, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_report.csv"',
        ]);
    }

    /**
     * View Audit Logs.
     */
    public function auditLogs(): JsonResponse
    {
        $logs = AuditLog::with('user')->latest()->paginate(25);

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs),
        ], 200);
    }

    /**
     * View Activity Logs.
     */
    public function activityLogs(): JsonResponse
    {
        $logs = ActivityLog::with('user')->latest()->paginate(25);

        return response()->json([
            'success' => true,
            'data' => ActivityLogResource::collection($logs),
        ], 200);
    }
}
