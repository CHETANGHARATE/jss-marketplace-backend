<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorStore;
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
        $pendingProductsCount = Product::whereIn('status', ['pending', 'pending_review'])->count();
        $totalVendors   = (int) VendorStore::count();
        $pendingVendors = (int) VendorStore::where('status', 'pending')->count();

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
            'total_revenue'              => (float) round($totalSales, 2),
            'total_sales'                => (float) round($totalSales, 2),
            'total_orders'               => (int) $totalOrders,
            'total_customers'            => (int) $totalCustomers,
            'total_products'             => (int) $totalProducts,
            'total_vendors'              => (int) $totalVendors,
            'total_categories'           => (int) \App\Models\Category::whereNull('parent_id')->count(),
            'low_stock_count'            => (int) $lowStockCount,
            'low_stock_alerts'           => (int) $lowStockCount,
            'pending_reviews'            => (int) $pendingReviewsCount,
            'pending_product_approvals'  => (int) $pendingProductsCount,
            'pending_vendor_approvals'   => (int) $pendingVendors,
            'system_health'              => 'healthy',
            'recent_orders'              => $recentOrders,
            'sales_chart'                => $salesTrend->map(fn ($r) => [
                'date'    => $r->date,
                'revenue' => (float) $r->total_sales,
                'orders'  => (int) $r->total_orders,
            ]),
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

        // Orders by status breakdown
        $ordersByStatus = Order::select('status', DB::raw('COUNT(id) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Revenue by day
        $revenueByDay = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->when($startDate && $endDate,
                fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
                fn ($q) => $q->where('created_at', '>=', now()->subDays(30))
            )
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'revenue' => (float) $r->revenue]);

        return [
            'total_revenue'       => $totalRevenue,
            'total_orders'        => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'orders_by_status'    => $ordersByStatus,
            'revenue_by_day'      => $revenueByDay,
            'payment_breakdown'   => $paymentBreakdown,
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
