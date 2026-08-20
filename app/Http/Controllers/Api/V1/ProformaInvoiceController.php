<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProformaInvoice;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProformaInvoiceController extends Controller
{
    /**
     * List Proforma Invoices for Buyer or Seller (Feature 95).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ProformaInvoice::with(['buyer.businessAccount', 'seller.vendorStore', 'purchaseOrder'])
            ->latest();

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $pis = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pis->items(),
            'meta' => [
                'current_page' => $pis->currentPage(),
                'last_page' => $pis->lastPage(),
                'total' => $pis->total(),
            ],
        ], 200);
    }

    /**
     * Create a Proforma Invoice from a Purchase Order (Feature 95).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'payment_instructions' => 'nullable|string|max:1000',
            'valid_until' => 'nullable|date|after:today',
        ]);

        $po = PurchaseOrder::with(['buyer.businessAccount', 'seller.vendorStore', 'items'])->findOrFail($validated['purchase_order_id']);

        if ($po->seller_id !== $userId && $po->buyer_id !== $userId && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $proformaNumber = 'PI-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $itemsSnapshot = $po->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ];
        })->toArray();

        $pi = ProformaInvoice::create([
            'proforma_number' => $proformaNumber,
            'purchase_order_id' => $po->id,
            'buyer_id' => $po->buyer_id,
            'seller_id' => $po->seller_id,
            'subtotal' => $po->subtotal,
            'tax_amount' => $po->tax_amount,
            'shipping_amount' => $po->shipping_amount,
            'total_amount' => $po->total_amount,
            'buyer_details' => [
                'name' => $po->buyer->businessAccount->legal_business_name ?? $po->buyer->name,
                'gstin' => $po->buyer->businessAccount->gstin ?? null,
                'address' => $po->buyer->businessAccount->registered_address ?? null,
            ],
            'seller_details' => [
                'store_name' => $po->seller->vendorStore->store_name ?? $po->seller->name,
                'gstin' => $po->seller->vendorStore->gstin ?? null,
            ],
            'items_snapshot' => $itemsSnapshot,
            'payment_instructions' => $validated['payment_instructions'] ?? 'Remit to JSS Solutions Marketplace Escrow Account via RTGS / NEFT / IMPS.',
            'valid_until' => $validated['valid_until'] ?? now()->addDays(15),
            'status' => 'generated',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proforma Invoice generated successfully.',
            'data' => $pi->load(['buyer.businessAccount', 'seller.vendorStore', 'purchaseOrder']),
        ], 201);
    }

    /**
     * Show Proforma Invoice details.
     */
    public function show(Request $request, string $proformaNumber): JsonResponse
    {
        $user = $request->user();

        $query = ProformaInvoice::where('proforma_number', $proformaNumber)
            ->with(['buyer.businessAccount', 'seller.vendorStore', 'purchaseOrder.items']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $pi = $query->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $pi,
        ], 200);
    }

    /**
     * Download Server-Side Generated Proforma Invoice PDF (Feature 95).
     */
    public function downloadPdf(Request $request, string $proformaNumber)
    {
        $user = $request->user();

        $query = ProformaInvoice::where('proforma_number', $proformaNumber)
            ->with(['buyer.businessAccount', 'seller.vendorStore', 'purchaseOrder']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $pi = $query->firstOrFail();

        try {
            $pdf = Pdf::loadView('invoices.proforma_invoice', compact('pi'));
            $pdf->setPaper('a4', 'portrait');

            $filename = "Proforma_Invoice_{$pi->proforma_number}.pdf";

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to render PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
