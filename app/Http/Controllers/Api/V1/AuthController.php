<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user account (Customer or Seller).
     * Prevents role escalation to Administrator.
     * Issues Email Verification OTP.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $roleInput = $validated['role'] ?? UserRole::CUSTOMER->value;
        $role = UserRole::tryFrom($roleInput) ?? UserRole::CUSTOMER;

        // Strictly prevent public registration as Administrator
        if ($role === UserRole::ADMIN) {
            $role = UserRole::CUSTOMER;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'status' => UserStatus::ACTIVE,
        ]);

        // Assign Spatie Role as single source of truth
        $user->assignRole($role->value);

        event(new Registered($user));

        // Generate 6-Digit Email Verification OTP
        $otpCode = sprintf('%06d', mt_rand(100000, 999999));
        Otp::where('email', $user->email)->where('type', 'email_verification')->update(['used' => true]);
        Otp::create([
            'email' => $user->email,
            'otp' => $otpCode,
            'type' => 'email_verification',
            'expires_at' => now()->addMinutes(10),
        ]);

        $responseData = ['email' => $user->email];
        // Strictly return demo_otp ONLY in local environment
        if (app()->environment('local')) {
            $responseData['demo_otp'] = $otpCode;
        }

        return response()->json([
            'success' => true,
            'requires_verification' => true,
            'message' => 'Account created. Please enter the 6-digit verification code sent to your email.',
            'data' => $responseData,
        ], 201);
    }

    /**
     * Verify Signup Email OTP and activate account.
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = strtolower($request->email);
        $otpCode = $request->otp;

        $otp = Otp::where('email', $email)
            ->where('otp', $otpCode)
            ->where('type', 'email_verification')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
            ], 404);
        }

        $user->email_verified_at = now();
        $user->save();

        $otp->used = true;
        $otp->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully. Account activated.',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Authenticate user & issue Sanctum Bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $loginField = strtolower($credentials['login'] ?? $credentials['email'] ?? $credentials['phone'] ?? '');

        // Support login by either email or phone
        $user = User::where('email', $loginField)
            ->orWhere('phone', $loginField)
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials provided.',
                'errors' => ['login' => ['The provided credentials do not match our records.']],
            ], 401);
        }

        if ($user->status === UserStatus::BANNED) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact platform support.',
            ], 403);
        }

        // Check if email verification is required
        if (!$user->email_verified_at && $user->role !== UserRole::ADMIN) {
            // Generate a fresh OTP for verification
            $otpCode = sprintf('%06d', mt_rand(100000, 999999));
            Otp::where('email', $user->email)->where('type', 'email_verification')->update(['used' => true]);
            Otp::create([
                'email' => $user->email,
                'otp' => $otpCode,
                'type' => 'email_verification',
                'expires_at' => now()->addMinutes(10),
            ]);

            $responseData = ['email' => $user->email];
            if (app()->environment('local')) {
                $responseData['demo_otp'] = $otpCode;
            }

            return response()->json([
                'success' => false,
                'requires_verification' => true,
                'message' => 'Your email address is not verified. Please verify your email to log in.',
                'data' => $responseData,
            ], 403);
        }

        // Issue new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
        ], 200);
    }

    /**
     * Update user profile details.
     */
    public function profile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user->fresh()),
        ], 200);
    }

    /**
     * Generate & send 6-digit OTP for Forgot Password.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = strtolower($request->email);
        $user = User::where('email', $email)->first();

        if ($user) {
            $otpCode = sprintf('%06d', mt_rand(100000, 999999));
            Otp::where('email', $email)->where('type', 'password_reset')->update(['used' => true]);
            Otp::create([
                'email' => $email,
                'otp' => $otpCode,
                'type' => 'password_reset',
                'expires_at' => now()->addMinutes(10),
            ]);

            $responseData = ['email' => $email];
            if (app()->environment('local')) {
                $responseData['demo_otp'] = $otpCode;
            }

            return response()->json([
                'success' => true,
                'message' => 'A 6-digit verification code has been sent to your email address.',
                'data' => $responseData,
            ], 200);
        }

        // Return uniform response to prevent user enumeration
        return response()->json([
            'success' => true,
            'message' => 'If an account with that email exists, a 6-digit verification code has been sent.',
        ], 200);
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'type' => 'nullable|string|in:password_reset,email_verification',
        ]);

        $email = strtolower($request->email);
        $otpCode = $request->otp;
        $type = $request->type ?? 'password_reset';

        $otp = Otp::where('email', $email)
            ->where('otp', $otpCode)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code verified successfully.',
        ], 200);
    }

    /**
     * Reset password using OTP code.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $email = strtolower($request->email);
        $otpCode = $request->token ?? $request->otp;
        $password = $request->password;

        $otp = Otp::where('email', $email)
            ->where('otp', $otpCode)
            ->where('type', 'password_reset')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            // Fallback check: if token was passed from Laravel link
            $user = User::where('email', $email)->first();
            if ($user && $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                return response()->json([
                    'success' => true,
                    'message' => 'Your password has been reset successfully.',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
                'errors' => ['otp' => ['Invalid or expired verification code.']],
            ], 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
            ], 404);
        }

        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        $otp->used = true;
        $otp->save();

        event(new PasswordReset($user));

        return response()->json([
            'success' => true,
            'message' => 'Your password has been reset successfully.',
        ], 200);
    }

    /**
     * Resend OTP code.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'nullable|string|in:password_reset,email_verification',
        ]);

        $email = strtolower($request->email);
        $type = $request->type ?? 'email_verification';

        $otpCode = sprintf('%06d', mt_rand(100000, 999999));
        Otp::where('email', $email)->where('type', $type)->update(['used' => true]);
        Otp::create([
            'email' => $email,
            'otp' => $otpCode,
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        $responseData = ['email' => $email];
        if (app()->environment('local')) {
            $responseData['demo_otp'] = $otpCode;
        }

        return response()->json([
            'success' => true,
            'message' => 'A new 6-digit verification code has been sent to your email.',
            'data' => $responseData,
        ], 200);
    }

    /**
     * Resend email verification notification.
     */
    public function sendVerificationNotification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email is already verified.',
            ], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification link sent to your email.',
        ], 200);
    }

    /**
     * Revoke active user token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }
}
