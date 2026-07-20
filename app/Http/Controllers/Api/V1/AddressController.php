<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Display a listing of customer's saved addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => AddressResource::collection($addresses),
        ], 200);
    }

    /**
     * Store a newly created address for customer.
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // If marked as default, unmark existing defaults
        if (!empty($validated['is_default'])) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $validated['user_id'] = $user->id;
        $address = Address::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully.',
            'data' => new AddressResource($address),
        ], 201);
    }

    /**
     * Delete an address (SoftDelete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], 404);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ], 200);
    }
}
