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
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\Services\Msg91Service;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Helper to audit and log SMTP configuration before mail dispatch.
     */
    private function logSmtpAudit(string $action, string $recipientEmail): void
    {
        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $encryption = config('mail.mailers.smtp.encryption');
        $username = config('mail.mailers.smtp.username');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        Log::info("AUDIT [{$action}]: Target Recipient [{$recipientEmail}], Mailer [{$mailer}], Host [{$host}:{$port}], Encryption [{$encryption}], Username [{$username}], From Address [{$fromAddress}], From Name [{$fromName}]");
    }

    /**
     * Central OTP mail dispatcher with full granular debug logging.
     * Logs all 8 required items. Does NOT suppress exceptions.
     *
     * @param string $action    Human-readable label for the dispatch context (e.g. "Register", "Login")
     * @param string $recipient Recipient email address
     * @param string $otpCode   The 6-digit OTP code being sent
     * @param string $type      OTP type: 'email_verification' or 'password_reset'
     * @throws \Throwable Re-throws any SMTP exception so the caller can return a 500 response
     */
    private function dispatchOtpMail(string $action, string $recipient, string $otpCode, string $type): void
    {
        // [1] Log: registration email / recipient received
        Log::info("OTP_DEBUG [{$action}][1/8] Recipient email received: [{$recipient}]");

        // [2] Log: OTP generated (masked last 2 digits for security, keep first 4 visible in debug)
        $maskedOtp = substr($otpCode, 0, 4) . '**';
        Log::info("OTP_DEBUG [{$action}][2/8] OTP generated: [{$maskedOtp}], type=[{$type}], length=[" . strlen($otpCode) . "]");

        // [3] Log: recipient passed to Mail::to()
        Log::info("OTP_DEBUG [{$action}][3/8] Passing recipient to Mail::to(): [{$recipient}]");

        // [4] Build OtpMail object and log success
        $otpMailObject = new OtpMail($otpCode, $type);
        Log::info("OTP_DEBUG [{$action}][4/8] OtpMail object created successfully: class=[" . get_class($otpMailObject) . "], view=[emails.otp], type=[{$type}]");

        // SMTP config dump before dispatch
        $this->logSmtpAudit("{$action} OTP Mail Dispatch", $recipient);

        // [5] Log: before Mail::to()->send()
        Log::info("OTP_DEBUG [{$action}][5/8] BEFORE Mail::to('{$recipient}')->send() — about to hand off to Symfony Mailer transport");

        try {
            Mail::to($recipient)->send($otpMailObject);

            // [6] Log: after successful send
            Log::info("OTP_DEBUG [{$action}][6/8] AFTER Mail::to('{$recipient}')->send() — SMTP transport accepted the message without exception");

        } catch (\Throwable $e) {
            // [7] Log: full exception message — do NOT suppress, re-throw
            Log::error("OTP_DEBUG [{$action}][7/8] EXCEPTION during Mail::to('{$recipient}')->send(): " . $e->getMessage());
            Log::error("OTP_DEBUG [{$action}][7/8] Exception class: " . get_class($e));
            Log::error("OTP_DEBUG [{$action}][7/8] Exception file: " . $e->getFile() . " line " . $e->getLine());
            Log::error("OTP_DEBUG [{$action}][7/8] Full stack trace:\n" . $e->getTraceAsString());

            // Re-throw — do NOT suppress
            throw $e;
        }
    }

    /**
     * Register a new user account (Customer or Seller).
     * Prevents role escalation to Administrator.
     * Issues Email Verification OTP via Laravel Mail.
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

        // Assign Spatie Role as single source of truth safely across sanctum, web, and api guards
        $user->assignRoleSafely($role->value);

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

        try {
            $this->dispatchOtpMail('Register', $user->email, $otpCode, 'email_verification');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deliver verification email via SMTP: ' . $e->getMessage(),
            ], 500);
        }

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

            try {
                $this->dispatchOtpMail('Login', $user->email, $otpCode, 'email_verification');
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deliver login verification email via SMTP: ' . $e->getMessage(),
                ], 500);
            }

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

            try {
                $this->dispatchOtpMail('ForgotPassword', $email, $otpCode, 'password_reset');
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deliver password reset email via SMTP: ' . $e->getMessage(),
                ], 500);
            }

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
     * Send Mobile OTP using MSG91 v5 API.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => 'required_without:phone|nullable|string',
            'phone' => 'required_without:mobile|nullable|string',
            'purpose' => 'required|string|in:login,signup',
        ]);

        $rawMobile = $request->mobile ?? $request->phone;
        $purpose = strtolower($request->purpose);
        $normalizedMobile = Msg91Service::normalizeMobile($rawMobile);

        $digits = preg_replace('/[^\d]/', '', $normalizedMobile);
        if (strlen($digits) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid 10-digit Indian mobile number.',
            ], 422);
        }

        // PURPOSE = LOGIN Check: Existing Account Required
        if ($purpose === 'login') {
            $user = User::where('phone', $normalizedMobile)
                ->orWhere('phone', ltrim($normalizedMobile, '+'))
                ->orWhere('phone', substr($digits, -10))
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_NOT_FOUND',
                    'message' => 'No account exists with this mobile number. Please sign up.',
                ], 404);
            }

            if ($user->status === UserStatus::BANNED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact platform support.',
                ], 403);
            }
        }

        // PURPOSE = SIGNUP Check: Unregistered Mobile Required
        if ($purpose === 'signup') {
            $existingUser = User::where('phone', $normalizedMobile)
                ->orWhere('phone', ltrim($normalizedMobile, '+'))
                ->orWhere('phone', substr($digits, -10))
                ->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'code' => 'MOBILE_ALREADY_REGISTERED',
                    'message' => 'This mobile number is already registered. Please login using OTP.',
                ], 422);
            }
        }

        // Call MSG91 Service to send OTP
        $msg91 = new Msg91Service();
        $result = $msg91->sendOtp($normalizedMobile);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }

        $reqId = $result['request_id'];

        // Store OTP transaction state locally for rate limiting and transaction tracking
        Otp::where('phone', $normalizedMobile)->where('purpose', $purpose)->update(['used' => true]);
        Otp::create([
            'phone' => $normalizedMobile,
            'purpose' => $purpose,
            'type' => 'mobile_otp',
            'otp' => '000000',
            'req_id' => $reqId,
            'attempts' => 0,
            'used' => false,
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'success' => true,
            'transaction_id' => $reqId,
            'req_id' => $reqId,
            'message' => 'OTP sent successfully to ' . $normalizedMobile,
        ], 200);
    }

    /**
     * Verify Mobile or Email OTP code.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $rawMobile = $request->mobile ?? $request->phone;

        // Fallback for Legacy Email Verification OTP if no mobile is passed
        if (!$rawMobile && $request->has('email') && !$request->has('purpose')) {
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

        $request->validate([
            'otp' => 'required|string|size:6',
            'purpose' => 'required|string|in:login,signup',
        ]);

        $purpose = strtolower($request->purpose);
        $normalizedMobile = Msg91Service::normalizeMobile($rawMobile);
        $reqId = $request->req_id ?? $request->transaction_id ?? $request->requestId;
        $otpCode = $request->otp;

        // Validate local OTP transaction state
        $otpTx = Otp::where('phone', $normalizedMobile)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otpTx) {
            return response()->json([
                'success' => false,
                'message' => 'Your OTP has expired or transaction is invalid. Please request a new OTP.',
            ], 400);
        }

        if ($otpTx->attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Too many verification attempts. Please request a new OTP.',
            ], 429);
        }

        // Call MSG91 Service as sole verification authority
        $msg91 = new Msg91Service();
        $verifyResult = $msg91->verifyOtp($normalizedMobile, $otpCode, $reqId ?: $otpTx->req_id);

        if (!$verifyResult['success']) {
            $otpTx->increment('attempts');
            return response()->json([
                'success' => false,
                'message' => $verifyResult['message'],
            ], 400);
        }

        // MSG91 Verified Successfully -> Mark transaction used
        $otpTx->update(['used' => true]);
        $digits = preg_replace('/[^\d]/', '', $normalizedMobile);

        // LOGIN FLOW: Authenticate existing account
        if ($purpose === 'login') {
            $user = User::where('phone', $normalizedMobile)
                ->orWhere('phone', ltrim($normalizedMobile, '+'))
                ->orWhere('phone', substr($digits, -10))
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_NOT_FOUND',
                    'message' => 'No account exists with this mobile number. Please sign up.',
                ], 404);
            }

            $user->phone_verified_at = now();
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'OTP login successful.',
                'data' => [
                    'user' => new UserResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        }

        // SIGNUP FLOW: Create Customer user account & authenticate
        if ($purpose === 'signup') {
            $existingUser = User::where('phone', $normalizedMobile)
                ->orWhere('phone', ltrim($normalizedMobile, '+'))
                ->orWhere('phone', substr($digits, -10))
                ->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'code' => 'MOBILE_ALREADY_REGISTERED',
                    'message' => 'This mobile number is already registered. Please login using OTP.',
                ], 422);
            }

            $name = trim($request->name ?? ('Customer ' . substr($digits, -4)));
            $email = strtolower($request->email ?? ('customer_' . substr($digits, -6) . '@jssmarketplace.local'));

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $normalizedMobile,
                'password' => Hash::make(Str::random(16)),
                'role' => UserRole::CUSTOMER,
                'status' => UserStatus::ACTIVE,
                'phone_verified_at' => now(),
            ]);

            $user->assignRoleSafely(UserRole::CUSTOMER->value);
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully via Mobile OTP.',
                'data' => [
                    'user' => new UserResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid transaction purpose.',
        ], 400);
    }

    /**
     * Send Email OTP for Login or Signup.
     */
    public function sendEmailOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|string|in:login,signup',
        ]);

        $email = strtolower($request->email);
        $purpose = strtolower($request->purpose);

        // PURPOSE = LOGIN Check: Existing Account Required
        if ($purpose === 'login') {
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_NOT_FOUND',
                    'message' => 'No account exists with this email address. Please sign up.',
                ], 404);
            }

            if ($user->status === UserStatus::BANNED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact platform support.',
                ], 403);
            }
        }

        // PURPOSE = SIGNUP Check: Unregistered Email Required
        if ($purpose === 'signup') {
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'code' => 'EMAIL_ALREADY_REGISTERED',
                    'message' => 'This email is already registered. Please login instead.',
                ], 422);
            }
        }

        $otpCode = sprintf('%06d', mt_rand(100000, 999999));
        Otp::where('email', $email)->where('purpose', $purpose)->update(['used' => true]);
        Otp::create([
            'email' => $email,
            'otp' => $otpCode,
            'type' => 'email_otp',
            'purpose' => $purpose,
            'attempts' => 0,
            'used' => false,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            $this->dispatchOtpMail('SendEmailOtp', $email, $otpCode, 'email_otp');
        } catch (\Throwable $e) {
            Log::warning("EMAIL_OTP_DELIVERY_NOTICE: SMTP error: " . $e->getMessage());
        }

        $responseData = ['email' => $email];
        if (app()->environment('local') || config('app.debug')) {
            $responseData['demo_otp'] = $otpCode;
        }

        return response()->json([
            'success' => true,
            'message' => 'A 6-digit verification code has been sent to ' . $email,
            'data' => $responseData,
        ], 200);
    }

    /**
     * Verify Email OTP code for Login, Signup, or Account Activation.
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'purpose' => 'nullable|string|in:login,signup,verification',
        ]);

        $email = strtolower($request->email);
        $otpCode = $request->otp;
        $purpose = strtolower($request->purpose ?? '');

        // Primary lookup matching email, OTP code, un-used status, and active expiration
        $otpRecord = Otp::where('email', $email)
            ->where('otp', $otpCode)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->when($purpose, function ($query, $p) {
                return $query->where(function ($q) use ($p) {
                    $q->where('purpose', $p)
                      ->orWhere('type', $p)
                      ->orWhere('type', 'email_verification')
                      ->orWhere('type', 'email_otp');
                });
            })
            ->orderBy('created_at', 'desc')
            ->first();

        // Fallback lookup if specific purpose flag was omitted
        if (!$otpRecord) {
            $otpRecord = Otp::where('email', $email)
                ->where('otp', $otpCode)
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect OTP or code has expired. Please check and try again.',
            ], 400);
        }

        $otpRecord->update(['used' => true]);
        $user = User::where('email', $email)->first();

        // FLOW A: Authenticate existing account or activate legacy password registration
        if ($purpose === 'login' || ($user && !$purpose) || ($user && $otpRecord->type === 'email_verification')) {
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_NOT_FOUND',
                    'message' => 'No account exists with this email address. Please sign up.',
                ], 404);
            }

            if ($user->status === UserStatus::BANNED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact platform support.',
                ], 403);
            }

            $user->email_verified_at = now();
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully. Login successful.',
                'data' => [
                    'user' => new UserResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        }

        // FLOW B: Create new Customer account via Email OTP Signup
        if ($purpose === 'signup' || (!$user && $purpose !== 'login')) {
            if ($user) {
                return response()->json([
                    'success' => false,
                    'code' => 'EMAIL_ALREADY_REGISTERED',
                    'message' => 'This email is already registered. Please login instead.',
                ], 422);
            }

            $name = trim($request->name ?? ('User ' . explode('@', $email)[0]));

            $newUser = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(16)),
                'role' => UserRole::CUSTOMER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]);

            $newUser->assignRoleSafely(UserRole::CUSTOMER->value);
            $token = $newUser->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully via Email OTP.',
                'data' => [
                    'user' => new UserResource($newUser),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
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
     * Resend Mobile or Email OTP code.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $rawMobile = $request->mobile ?? $request->phone;

        if (!$rawMobile && $request->has('email')) {
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

            try {
                $this->dispatchOtpMail('ResendOtp', $email, $otpCode, $type);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resend verification email via SMTP: ' . $e->getMessage(),
                ], 500);
            }

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

        $request->validate([
            'purpose' => 'nullable|string|in:login,signup',
        ]);

        $purpose = strtolower($request->purpose ?? 'login');
        $normalizedMobile = Msg91Service::normalizeMobile($rawMobile);

        $latestTx = Otp::where('phone', $normalizedMobile)
            ->where('purpose', $purpose)
            ->orderBy('created_at', 'desc')
            ->first();

        // Enforce 30 second resend cooldown
        if ($latestTx && $latestTx->created_at->gt(now()->subSeconds(30))) {
            $waitSeconds = 30 - now()->diffInSeconds($latestTx->created_at);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$waitSeconds} seconds before requesting another OTP.",
            ], 429);
        }

        $msg91 = new Msg91Service();
        $result = $msg91->resendOtp($normalizedMobile, $latestTx?->req_id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }

        $newReqId = $result['request_id'] ?? $latestTx?->req_id;

        Otp::create([
            'phone' => $normalizedMobile,
            'purpose' => $purpose,
            'type' => 'mobile_otp',
            'otp' => '000000',
            'req_id' => $newReqId,
            'attempts' => 0,
            'used' => false,
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'success' => true,
            'transaction_id' => $newReqId,
            'req_id' => $newReqId,
            'message' => 'A new OTP code has been sent via SMS.',
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
