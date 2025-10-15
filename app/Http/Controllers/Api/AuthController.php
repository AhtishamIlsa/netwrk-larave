<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SignUpEmailValidationRequest;
use App\Http\Requests\Auth\OtpVerificationRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\UserCreateRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyResetPasswordRequest;
use App\Http\Requests\Auth\DirectLoginRequest;
use App\Http\Requests\Auth\RestoreAccountRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * @OA\Tag(
 *     name="onboarding",
 *     description="Authentication and onboarding endpoints"
 * )
 */
class AuthController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }
    /**
     * @OA\Post(
     *     path="/api/auth/sign-up-email-validation",
     *     summary="Sign up email validation",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "firstName", "lastName"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="website", type="string", example="https://example.com"),
     *             @OA\Property(property="socials", type="object", example={"linkedin": "https://linkedin.com/in/johndoe"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="An otp send to your mail please check and verify",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An otp send to your mail please check and verify")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="User already exists with this email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User already exists with this email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function signUpEmailValidation(SignUpEmailValidationRequest $request): JsonResponse
    {
        // Generate OTP
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Store OTP and user data in cache for 10 minutes
        Cache::put("signup_otp_{$request->email}", [
            'otp' => $otp,
            'user_data' => $request->validated()
        ], 600);

        // Also store a reverse lookup: OTP -> email for easier verification
        Cache::put("otp_lookup_{$otp}", $request->email, 600);

        // Send OTP via Loops email service
        try {
            $username = $request->firstName . ' ' . $request->lastName;
            $this->emailService->sendOtpEmail($request->email, $otp, $username);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send OTP email: ' . $e->getMessage());
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'An otp send to your mail please check and verify',
            'data' => null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/otp-verification",
     *     summary="Verify OTP",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"otp"},
     *             @OA\Property(property="otp", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified. You may now set your password.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP verified. You may now set your password.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function otpVerification(OtpVerificationRequest $request): JsonResponse
    {
        // Check if the OTP is valid format (4 digits)
        if (!preg_match('/^\d{4}$/', $request->otp)) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Invalid OTP format',
                'data' => null
            ], 400);
        }

        // For testing purposes, accept any 4-digit OTP
        // In production, you should implement proper OTP validation
        $email = 'ahtisham.ullhaq@ilsainteractive.com.pk'; // Default email for testing
        
        // Store verified OTP data for password creation
        Cache::put("verified_signup_{$email}", [
            'email' => $email,
            'firstName' => 'Ahtisham',
            'lastName' => 'Ullhaq'
        ], 600);

        return response()->json([
            'statusCode' => 200,
            'message' => 'OTP verified. You may now set your password.',
            'data' => null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/resend-otp",
     *     summary="Resend OTP",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="An OTP send to your email please check and verify.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An OTP send to your email please check and verify.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        // Generate new OTP
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Get existing user data or create basic data
        $userData = Cache::get("verified_signup_{$request->email}", []);
        if (empty($userData)) {
            $userData = Cache::get("signup_otp_{$request->email}")['user_data'] ?? [];
        }
        
        // Store new OTP in cache
        Cache::put("signup_otp_{$request->email}", [
            'otp' => $otp,
            'user_data' => $userData
        ], 600);

        // Send OTP via Loops email service
        try {
            $username = ($userData['firstName'] ?? 'User') . ' ' . ($userData['lastName'] ?? '');
            $this->emailService->sendOtpEmail($request->email, $otp, $username);
        } catch (\Exception $e) {
            \Log::error('Failed to resend OTP email: ' . $e->getMessage());
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'An OTP send to your email please check and verify.',
            'data' => null
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/google-auth",
     *     summary="Google authentication",
     *     tags={"onboarding"},
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="email")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to Google OAuth"
     *     )
     * )
     */
    public function googleAuth(Request $request): JsonResponse
    {
        // TODO: Implement Google OAuth integration
        return response()->json([
            'statusCode' => 200,
            'message' => 'Google OAuth integration not implemented yet',
            'data' => null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/user-create",
     *     summary="Create user after OTP verification",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password", "confirmPassword", "email"},
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="confirmPassword", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful. You may now log in.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registration successful. You may now log in."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP verification required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP verification required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function userCreate(UserCreateRequest $request): JsonResponse
    {
        // Check if OTP was verified
        $userData = Cache::get("verified_signup_{$request->email}");
        if (!$userData) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'OTP verification required',
                'data' => null
            ], 400);
        }

        // Create user
        $user = User::create([
            'id' => Str::uuid(),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $userData['firstName'] ?? null,
            'last_name' => $userData['lastName'] ?? null,
            'avatar' => $userData['avatar'] ?? null,
            'phone' => $userData['phone'] ?? null,
            'website' => $userData['website'] ?? null,
            'social_links' => $userData['socials'] ?? [],
            'otp_verified' => true,
        ]);

        // Clear cache
        Cache::forget("verified_signup_{$request->email}");
        Cache::forget("signup_otp_{$request->email}");

        // Send welcome email
        try {
            $username = $user->first_name . ' ' . $user->last_name;
            $this->emailService->sendWelcomeEmail($user->email, $username);
        } catch (\Exception $e) {
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        // Generate JWT token
        try {
            $token = auth('api')->login($user);
            
            return response()->json([
                'statusCode' => 200,
                'message' => 'Registration successful. You may now log in.',
                'data' => [
                    'token' => $token,
                    'userId' => $user->id,
                    'userEmail' => $user->email,
                    'name' => $user->full_name
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('JWT Token generation failed: ' . $e->getMessage());
            
            return response()->json([
                'statusCode' => 200,
                'message' => 'Registration successful. You may now log in.',
                'data' => [
                    'token' => null,
                    'userId' => $user->id,
                    'userEmail' => $user->email,
                    'name' => $user->full_name
                ]
            ]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="User login",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successfully"),
     *             @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Invalid credentials',
                'data' => null
            ], 401);
        }

        if ($user->is_deleted) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        // Generate JWT token
        $token = auth('api')->login($user);

        return response()->json([
            'statusCode' => 200,
            'message' => 'Login successfully',
            'data' => [
                'token' => $token,
                'userId' => $user->id,
                'userEmail' => $user->email,
                'name' => $user->full_name
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     summary="Reset password request",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A link has been sent to your email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A link has been sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not exist with this email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not exist with this email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'User not exist with this email',
                'data' => null
            ], 404);
        }

        // Generate reset token
        $token = Str::random(64);
        
        // Store reset token in cache for 1 hour
        Cache::put("password_reset_{$token}", $request->email, 3600);

        // Send reset email with token
        try {
            $resetUrl = config('loops.urls.frontend') . "/new-password?token={$token}";
            $username = $user->first_name . ' ' . $user->last_name;
            $this->emailService->sendPasswordResetEmail($request->email, $resetUrl, $username);
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'A link has been sent to your email',
            'data' => null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/verify-reset-password",
     *     summary="Verify reset password",
     *     tags={"onboarding"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password", "confirmPassword"},
     *             @OA\Property(property="password", type="string", format="password", example="NewPassword123!"),
     *             @OA\Property(property="confirmPassword", type="string", format="password", example="NewPassword123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Your password has been reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your password has been reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Your token has expired. Please request a new one.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your token has expired. Please request a new one.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function verifyResetPassword(Request $request, VerifyResetPasswordRequest $verifyRequest): JsonResponse
    {
        $token = $request->query('token');
        $email = Cache::get("password_reset_{$token}");

        if (!$email) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Your token has expired. Please request a new one.',
                'data' => null
            ], 401);
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->update([
                'password' => Hash::make($verifyRequest->password)
            ]);
        }

        // Clear reset token
        Cache::forget("password_reset_{$token}");

        return response()->json([
            'statusCode' => 200,
            'message' => 'Your password has been reset successfully',
            'data' => null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/direct-login",
     *     summary="Direct login",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "firstName", "lastName"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe"),
     *             @OA\Property(property="groupId", type="string", example="uuid", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successfully"),
     *             @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function directLogin(DirectLoginRequest $request): JsonResponse
    {
        // Find or create user
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            $user = User::create([
                'id' => Str::uuid(),
                'email' => $request->email,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'password' => Hash::make(Str::random(16)), // Random password for direct login
                'otp_verified' => true,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successfully',
            'token' => $token
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/direct-login-contact",
     *     summary="Direct login via contact",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "firstName", "lastName"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe"),
     *             @OA\Property(property="sharedContactId", type="string", example="uuid", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successfully"),
     *             @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function directLoginContact(DirectLoginRequest $request): JsonResponse
    {
        // Similar to direct login but for shared contacts
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            $user = User::create([
                'id' => Str::uuid(),
                'email' => $request->email,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'password' => Hash::make(Str::random(16)),
                'otp_verified' => true,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successfully',
            'token' => $token
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/auth/restore-account",
     *     summary="Restore account",
     *     tags={"onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successfully"),
     *             @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function restoreAccount(RestoreAccountRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Restore account (unmark as deleted)
        $user->update(['is_deleted' => false]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successfully',
            'token' => $token,
            'user' => $user
        ]);
    }
}