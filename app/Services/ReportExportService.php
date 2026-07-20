<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;

class ReportExportService
{
    /**
     * Export sales report data in CSV string or JSON array.
     */
    public function exportSalesReport(string $format = 'csv', ?string $startDate = null, ?string $endDate = null): string|array
    {
        $query = Order::with('user');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $orders = $query->latest()->get();

        if ($format === 'json') {
            return $orders->toArray();
        }

        // Generate CSV output
        $csv = "Order Number,Customer Name,Customer Email,Status,Payment Status,Total Amount,Date\n";

        foreach ($orders as $order) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",%.2f,\"%s\"\n",
                $order->order_number,
                $order->user?->name ?? 'Guest',
                $order->user?->email ?? 'N/A',
                $order->status,
                $order->payment_status,
                $order->total_amount,
                $order->created_at->toDateTimeString()
            );
        }

        return $csv;
    }

    /**
     * Export inventory report data in CSV string or JSON array.
     */
    public function exportInventoryReport(string $format = 'csv'): string|array
    {
        $products = Product::with('category')->latest()->get();

        if ($format === 'json') {
            return $products->toArray();
        }

        $csv = "SKU,Product Name,Category,Price,Stock Quantity,Stock Status\n";

        foreach ($products as $product) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",%.2f,%d,\"%s\"\n",
                $product->sku ?? 'N/A',
                $product->name,
                $product->category?->name['en'] ?? 'N/A',
                $product->original_price,
                $product->stock_quantity,
                $product->stock_status
            );
        }

        return $csv;
    }
}
