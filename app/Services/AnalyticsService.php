<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get high-level overview metrics for Admin Dashboard.
     */
    public function getDashboardOverview(): array
    {
        $totalSales = Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total_amount');
        $totalOrders = Order::count();
        $totalCustomers = User::whereHas('roles', function ($q) {
            $q->where('name', 'customer');
        })->count();
        $totalProducts = Product::count();
        $lowStockCount = Product::where('stock_quantity', '<=', 5)->count();
        $pendingReviewsCount = Review::where('status', 'pending')->count();

        $recentOrders = Order::with('user')->latest()->take(5)->get();

        // 30 Days Sales Trend
        $salesTrend = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total_sales'),
            DB::raw('COUNT(id) as total_orders')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        return [
            'metrics' => [
                'total_sales' => (float) round($totalSales, 2),
                'total_orders' => (int) $totalOrders,
                'total_customers' => (int) $totalCustomers,
                'total_products' => (int) $totalProducts,
                'low_stock_alerts' => (int) $lowStockCount,
                'pending_reviews' => (int) $pendingReviewsCount,
            ],
            'recent_orders' => $recentOrders,
            'sales_trend' => $salesTrend,
        ];
    }

    /**
     * Get detailed sales analytics.
     */
    public function getSalesAnalytics(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalRevenue = (float) $query->sum('total_amount');
        $totalOrders = (int) $query->count();
        $averageOrderValue = $totalOrders > 0 ? (float) round($totalRevenue / $totalOrders, 2) : 0.0;

        // Breakdown by Payment Status
        $paymentBreakdown = Payment::select('gateway', DB::raw('COUNT(id) as count'), DB::raw('SUM(amount) as total'))
            ->where('status', 'captured')
            ->groupBy('gateway')
            ->get();

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'payment_breakdown' => $paymentBreakdown,
        ];
    }

    /**
     * Get customer analytics.
     */
    public function getCustomerAnalytics(): array
    {
        $totalCustomers = User::role('customer')->count();

        // Top 5 buyers by total spent
        $topCustomers = User::role('customer')
            ->withSum(['orders' => function ($q) {
                $q->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);
            }], 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->take(5)
            ->get();

        return [
            'total_customers' => $totalCustomers,
            'top_customers' => $topCustomers->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'total_spent' => (float) ($u->orders_sum_total_amount ?? 0),
            ]),
        ];
    }

    /**
     * Get inventory analytics.
     */
    public function getInventoryAnalytics(): array
    {
        $totalStockValue = Product::select(DB::raw('SUM(original_price * stock_quantity) as total_value'))->value('total_value');
        $lowStockProducts = Product::where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 5)->get();
        $outOfStockCount = Product::where('stock_quantity', '<=', 0)->count();

        return [
            'total_stock_value' => (float) round($totalStockValue ?? 0, 2),
            'low_stock_count' => $lowStockProducts->count(),
            'out_of_stock_count' => $outOfStockCount,
            'low_stock_items' => $lowStockProducts,
        ];
    }
}
