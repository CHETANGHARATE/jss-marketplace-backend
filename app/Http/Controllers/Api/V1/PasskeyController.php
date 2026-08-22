<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PasskeyCredential;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PasskeyController extends Controller
{
    /**
     * Generate WebAuthn challenge for registering a new passkey.
     */
    public function generateRegisterOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $challenge = Str::random(32);

        // Store challenge in cache for 5 minutes
        Cache::put("passkey_reg_challenge_{$user->id}", $challenge, now()->addMinutes(5));

        $options = [
            'challenge' => base64_encode($challenge),
            'rp' => [
                'name' => 'JSS Solutions Marketplace',
                'id' => $request->getHost(),
            ],
            'user' => [
                'id' => base64_encode((string) $user->id),
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'residentKey' => 'preferred',
                'userVerification' => 'preferred',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $options,
        ], 200);
    }

    /**
     * Verify and store WebAuthn passkey credential.
     */
    public function verifyRegister(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'credential_id' => 'required|string',
            'public_key' => 'required|string',
            'device_name' => 'nullable|string|max:100',
            'transports' => 'nullable|array',
        ]);

        $challenge = Cache::get("passkey_reg_challenge_{$user->id}");
        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'Registration challenge expired. Please try again.',
            ], 422);
        }

        Cache::forget("passkey_reg_challenge_{$user->id}");

        $credential = PasskeyCredential::updateOrCreate(
            ['credential_id' => $validated['credential_id']],
            [
                'user_id' => $user->id,
                'public_key' => $validated['public_key'],
                'device_name' => $validated['device_name'] ?? 'Biometric Device',
                'transports' => $validated['transports'] ?? ['internal'],
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Passkey registered successfully! You can now log in using Face ID or Fingerprint.',
            'data' => $credential,
        ], 200);
    }

    /**
     * Generate WebAuthn challenge for logging in with a passkey.
     */
    public function generateLoginOptions(Request $request): JsonResponse
    {
        $challenge = Str::random(32);
        $sessionId = Str::random(24);

        Cache::put("passkey_auth_challenge_{$sessionId}", $challenge, now()->addMinutes(5));

        $options = [
            'challenge' => base64_encode($challenge),
            'timeout' => 60000,
            'rpId' => $request->getHost(),
            'userVerification' => 'preferred',
            'session_token' => $sessionId,
        ];

        return response()->json([
            'success' => true,
            'data' => $options,
        ], 200);
    }

    /**
     * Verify passkey and log user into the platform.
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credential_id' => 'required|string',
            'session_token' => 'required|string',
        ]);

        $challenge = Cache::get("passkey_auth_challenge_{$validated['session_token']}");
        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication challenge expired. Please try again.',
            ], 422);
        }

        $credential = PasskeyCredential::where('credential_id', $validated['credential_id'])
            ->with('user')
            ->first();

        if (!$credential || !$credential->user) {
            return response()->json([
                'success' => false,
                'message' => 'Passkey not recognized. Please sign in with email/password.',
            ], 404);
        }

        $user = $credential->user;

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is suspended. Please contact customer support.',
            ], 403);
        }

        // Update credential usage
        $credential->increment('sign_count');
        $credential->update(['last_used_at' => now()]);
        Cache::forget("passkey_auth_challenge_{$validated['session_token']}");

        // Create Sanctum Token
        $token = $user->createToken('passkey_auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Passkey login successful!',
            'token' => $token,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                ],
            ],
        ], 200);
    }

    /**
     * List user's registered passkeys.
     */
    public function index(Request $request): JsonResponse
    {
        $passkeys = PasskeyCredential::where('user_id', $request->user()->id)
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $passkeys,
        ], 200);
    }

    /**
     * Delete a passkey.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        PasskeyCredential::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Passkey removed successfully.',
        ], 200);
    }
}
