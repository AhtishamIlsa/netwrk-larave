<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactsController;
use App\Http\Controllers\Api\GroupsController;
use App\Http\Controllers\Api\MakeAnIntroController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReferralsController;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
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
    Route::get('/graph/contact-industry', [UserController::class, 'getGraphData'])->middleware('auth:api');
    Route::post('/delete', [UserController::class, 'deleteUsers'])->middleware('auth:api');
    Route::delete('/delete/secondary-profile', [UserController::class, 'deleteUsersSecondaryProfile']);
    Route::patch('/update-profile', [UserController::class, 'updateProfile'])->middleware('auth:api');
    Route::post('/secondry-profile', [UserController::class, 'createUserSecondryProfile'])->middleware('auth:api');
    Route::post('/socials-preferences', [UserController::class, 'updateUserSocialsPreferences'])->middleware('auth:api');
    Route::get('/me', [UserController::class, 'me'])->middleware('auth:api');
    Route::get('/dashboard', [UserController::class, 'dashboard'])->middleware('auth:api');
    Route::get('/dashboard/graph/location', [UserController::class, 'getUserLocationGraph'])->middleware('auth:api');
    Route::get('/industries', [UserController::class, 'getIndustries']);
});

// Contacts Routes (matching NestJS structure)
Route::prefix('contacts')->middleware('auth:api')->group(function () {
    // List and search contacts
    Route::get('/', [ContactsController::class, 'getContacts']);
    
    // Analytics and stats
    Route::get('/indirect-contacts', [ContactsController::class, 'getIndirectContacts']);
    Route::get('/graph/{year}', [ContactsController::class, 'getContactsChartData']);
    
    // CRUD operations
    Route::post('/create-contact', [ContactsController::class, 'createContact']);
    Route::get('/get-contact/{contactId}', [ContactsController::class, 'getSingleContact']);
    Route::patch('/update-contact/{contactId}', [ContactsController::class, 'updateContact']);
    Route::post('/delete', [ContactsController::class, 'deleteContacts']);
});

// Groups Routes (matching NestJS structure)
Route::prefix('groups')->middleware('auth:api')->group(function () {
    Route::get('/find-users-groups', [GroupsController::class, 'findUsersGroups']);
    Route::get('/group-list', [GroupsController::class, 'getGroupsList']);
    Route::post('/create-group', [GroupsController::class, 'createGroup']);
});

// Referrals Routes (matching NestJS structure)
Route::prefix('referrals')->middleware('auth:api')->group(function () {
    Route::get('/get-user-referrals', [ReferralsController::class, 'getUserReferrals']);
    Route::post('/update-status/{introductionId}', [ReferralsController::class, 'updateStatus']);
    Route::post('/update-request-status/{introductionId}', [ReferralsController::class, 'updateRequestStatus']);
    Route::get('/get-detail/{introductionId}', [ReferralsController::class, 'getDetail']);
    Route::post('/send-reminder/{introductionId}', [ReferralsController::class, 'sendReminder']);
    Route::post('/revoke-referral/{introductionId}', [ReferralsController::class, 'revokeReferral']);
});

// Make An Intro Routes (matching NestJS structure)
Route::prefix('make-an-intro')->middleware('auth:api')->group(function () {
    Route::post('/validation', [MakeAnIntroController::class, 'validation']);
    Route::post('/', [MakeAnIntroController::class, 'create']);
});

