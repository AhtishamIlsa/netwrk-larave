<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Onboarding/Auth Routes (matching NestJS structure)
Route::prefix('auth')->group(function () {
    Route::post('/sign-up-email-validation', [AuthController::class, 'signUpEmailValidation']);
    Route::post('/otp-verification', [AuthController::class, 'otpVerification']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::get('/google-auth', [AuthController::class, 'googleAuth']);
    Route::post('/user-create', [AuthController::class, 'userCreate']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/verify-reset-password', [AuthController::class, 'verifyResetPassword']);
    Route::post('/direct-login', [AuthController::class, 'directLogin']);
    Route::post('/direct-login-contact', [AuthController::class, 'directLoginContact']);
    Route::put('/restore-account', [AuthController::class, 'restoreAccount']);
});

// Users Routes (matching NestJS structure)
Route::prefix('users')->group(function () {
    Route::get('/graph/contact-industry', [UserController::class, 'getGraphData'])->middleware('auth:sanctum');
    Route::post('/delete', [UserController::class, 'deleteUsers'])->middleware('auth:sanctum');
    Route::delete('/delete/secondary-profile', [UserController::class, 'deleteUsersSecondaryProfile']);
    Route::patch('/update-profile', [UserController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::post('/secondry-profile', [UserController::class, 'createUserSecondryProfile'])->middleware('auth:sanctum');
    Route::post('/socials-preferences', [UserController::class, 'updateUserSocialsPreferences'])->middleware('auth:sanctum');
    Route::get('/me', [UserController::class, 'me'])->middleware('auth:sanctum');
    Route::get('/dashboard', [UserController::class, 'dashboard'])->middleware('auth:sanctum');
    Route::get('/dashboard/graph/location', [UserController::class, 'getUserLocationGraph'])->middleware('auth:sanctum');
    Route::get('/industries', [UserController::class, 'getIndustries']);
});

