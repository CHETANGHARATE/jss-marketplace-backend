<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationNegotiation;
use App\Models\Rfq;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    /**
     * Seller: Submit a quotation for an RFQ (Feature 83).
     */
    public function submitQuotation(Request $request, int $rfqId): JsonResponse
    {
        $sellerId = $request->user()->id;
        $rfq = Rfq::findOrFail($rfqId);

        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:1',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'required|integer|min:1',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'seller_notes' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
        ]);

        $subtotal = $validated['unit_price'] * $validated['quantity'];
        $shipping = (float) ($validated['shipping_cost'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $total = $subtotal + $shipping + $tax;

        $quotationNumber = 'QTN-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $quotation = Quotation::updateOrCreate(
            [
                'rfq_id' => $rfq->id,
                'seller_id' => $sellerId,
            ],
            [
                'quotation_number' => $quotationNumber,
                'unit_price' => $validated['unit_price'],
                'quantity' => $validated['quantity'],
                'moq' => $validated['moq'] ?? 1,
                'lead_time_days' => $validated['lead_time_days'],
                'shipping_cost' => $shipping,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'valid_until' => $validated['valid_until'] ?? now()->addDays(15),
                'seller_notes' => $validated['seller_notes'] ?? null,
                'attachments' => $validated['attachments'] ?? [],
                'status' => 'submitted',
            ]
        );

        // Update RFQ status to quotation_received if currently submitted
        if ($rfq->status === 'submitted') {
            $rfq->update(['status' => 'quotation_received']);
        }

        // Notify Buyer
        try {
            UserNotification::create([
                'user_id' => $rfq->user_id,
                'title' => 'New Quotation Received! 💼',
                'message' => "A seller submitted a quotation of ₹" . number_format($total, 2) . " (₹{$validated['unit_price']}/unit) for RFQ #{$rfq->rfq_number}.",
                'type' => 'quotation_received',
                'data' => [
                    'rfq_id' => $rfq->id,
                    'quotation_id' => $quotation->id,
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Quotation submitted successfully.',
            'data' => $quotation->load('seller'),
        ], 201);
    }

    /**
     * Buyer / Seller: Multi-Round Negotiation & Counter-Offer (Phase 4F).
     */
    public function counterOffer(Request $request, int $quotationId): JsonResponse
    {
        $user = $request->user();
        $quotation = Quotation::with('rfq')->findOrFail($quotationId);

        $isBuyer = $quotation->rfq->user_id === $user->id;
        $isSeller = $quotation->seller_id === $user->id;

        if (!$isBuyer && !$isSeller && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'offer_price' => 'required|numeric|min:0.01',
            'quantity' => 'nullable|integer|min:1',
            'message' => 'nullable|string|max:1000',
        ]);

        $actorType = $isBuyer ? 'buyer' : 'seller';

        $negotiation = QuotationNegotiation::create([
            'quotation_id' => $quotation->id,
            'user_id' => $user->id,
            'actor_type' => $actorType,
            'offer_price' => $validated['offer_price'],
            'quantity' => $validated['quantity'] ?? $quotation->quantity,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        $quotation->update([
            'status' => 'countered',
            'unit_price' => $validated['offer_price'],
            'quantity' => $validated['quantity'] ?? $quotation->quantity,
            'total_amount' => ($validated['offer_price'] * ($validated['quantity'] ?? $quotation->quantity)) + $quotation->shipping_cost + $quotation->tax_amount,
        ]);

        $quotation->rfq->update(['status' => 'negotiation']);

        // Notify opposite party
        $recipientId = $isBuyer ? $quotation->seller_id : $quotation->rfq->user_id;
        try {
            UserNotification::create([
                'user_id' => $recipientId,
                'title' => 'Counter-Offer Received 🔄',
                'message' => "A counter-offer of ₹{$validated['offer_price']}/unit was made on Quotation #{$quotation->quotation_number}.",
                'type' => 'quotation_countered',
                'data' => [
                    'quotation_id' => $quotation->id,
                    'offer_price' => $validated['offer_price'],
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Counter-offer submitted.',
            'data' => [
                'negotiation' => $negotiation,
                'quotation' => $quotation->fresh(['negotiations.user', 'seller']),
            ],
        ], 200);
    }

    /**
     * Buyer: Accept a quotation (Feature 83).
     */
    public function acceptQuotation(Request $request, int $quotationId): JsonResponse
    {
        $userId = $request->user()->id;
        $quotation = Quotation::with('rfq')->findOrFail($quotationId);

        if ($quotation->rfq->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $quotation->update(['status' => 'accepted']);
        $quotation->rfq->update(['status' => 'accepted']);

        // Reject other pending quotations for this RFQ
        Quotation::where('rfq_id', $quotation->rfq_id)
            ->where('id', '!=', $quotation->id)
            ->where('status', 'submitted')
            ->update(['status' => 'rejected']);

        // Notify Seller
        try {
            UserNotification::create([
                'user_id' => $quotation->seller_id,
                'title' => 'Quotation Accepted! 🎉',
                'message' => "Great news! Your quotation #{$quotation->quotation_number} for RFQ #{$quotation->rfq->rfq_number} was accepted by the buyer. Generate PO/Proforma to proceed.",
                'type' => 'quotation_accepted',
                'data' => [
                    'quotation_id' => $quotation->id,
                    'rfq_id' => $quotation->rfq_id,
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Quotation accepted! You can now generate a Purchase Order or Proforma Invoice.',
            'data' => $quotation->fresh(['rfq', 'seller']),
        ], 200);
    }

    /**
     * Buyer / Seller: Reject a quotation.
     */
    public function rejectQuotation(Request $request, int $quotationId): JsonResponse
    {
        $userId = $request->user()->id;
        $quotation = Quotation::with('rfq')->findOrFail($quotationId);

        if ($quotation->rfq->user_id !== $userId && $quotation->seller_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $quotation->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Quotation rejected.',
        ], 200);
    }
}
