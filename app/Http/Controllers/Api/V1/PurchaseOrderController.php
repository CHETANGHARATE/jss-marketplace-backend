<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\UserNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    /**
     * List Purchase Orders for authenticated Buyer or Seller (Feature 88).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PurchaseOrder::with(['buyer.businessAccount', 'seller.vendorStore', 'items.product'])
            ->latest();

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $pos = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pos->items(),
            'meta' => [
                'current_page' => $pos->currentPage(),
                'last_page' => $pos->lastPage(),
                'total' => $pos->total(),
            ],
        ], 200);
    }

    /**
     * Create a Purchase Order from an accepted Quotation (Feature 88).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:100',
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $quotation = Quotation::with('rfq')->findOrFail($validated['quotation_id']);

        if ($quotation->rfq->user_id !== $userId && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $subtotal = $quotation->unit_price * $quotation->quantity;
        $tax = (float) $quotation->tax_amount;
        $shipping = (float) $quotation->shipping_cost;
        $total = $subtotal + $tax + $shipping;

        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'quotation_id' => $quotation->id,
            'rfq_id' => $quotation->rfq_id,
            'buyer_id' => $userId,
            'seller_id' => $quotation->seller_id,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'total_amount' => $total,
            'payment_terms' => $validated['payment_terms'] ?? 'Net 30',
            'delivery_terms' => $validated['delivery_terms'] ?? 'Door Delivery',
            'billing_address' => $validated['billing_address'] ?? null,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        // Create PO Line Item
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $quotation->rfq->product_id,
            'product_name' => $quotation->rfq->title,
            'quantity' => $quotation->quantity,
            'unit_price' => $quotation->unit_price,
            'tax_percent' => 18.00,
            'total_price' => $subtotal,
        ]);

        // Update RFQ status
        $quotation->rfq->update(['status' => 'converted_to_po']);

        // Notify Seller
        try {
            UserNotification::create([
                'user_id' => $quotation->seller_id,
                'title' => 'Purchase Order Issued! 📄',
                'message' => "Purchase Order #{$po->po_number} for ₹" . number_format($total, 2) . " has been issued to your store. Review and accept to start fulfillment.",
                'type' => 'po_issued',
                'data' => [
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order generated and issued successfully.',
            'data' => $po->load(['buyer.businessAccount', 'seller.vendorStore', 'items']),
        ], 201);
    }

    /**
     * Show PO details.
     */
    public function show(Request $request, string $poNumber): JsonResponse
    {
        $user = $request->user();

        $query = PurchaseOrder::where('po_number', $poNumber)
            ->with(['buyer.businessAccount', 'seller.vendorStore', 'items.product', 'proformaInvoice']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $po = $query->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $po,
        ], 200);
    }

    /**
     * Download Server-Side Generated Purchase Order PDF (Feature 88).
     */
    public function downloadPdf(Request $request, string $poNumber)
    {
        $user = $request->user();

        $query = PurchaseOrder::where('po_number', $poNumber)
            ->with(['buyer.businessAccount', 'seller.vendorStore', 'items.product']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $po = $query->firstOrFail();

        try {
            $pdf = Pdf::loadView('invoices.purchase_order', compact('po'));
            $pdf->setPaper('a4', 'portrait');

            $filename = "PO_{$po->po_number}.pdf";

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to render PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seller: Accept Purchase Order.
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $po = PurchaseOrder::findOrFail($id);

        if ($po->seller_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $po->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order accepted.',
            'data' => $po,
        ], 200);
    }
}
