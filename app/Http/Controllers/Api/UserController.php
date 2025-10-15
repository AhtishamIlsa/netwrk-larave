<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\CreateSecondaryProfileRequest;
use App\Http\Requests\User\UpdateSocialsPreferencesRequest;
use App\Http\Requests\User\DeleteUsersRequest;
use App\Http\Requests\User\GetUserDashboardLocationRequest;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

/**
 * @OA\Tag(
 *     name="users",
 *     description="User management endpoints"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users/graph/contact-industry",
     *     summary="Get user contact industries graph data",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getGraphData(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        // TODO: Implement contact industries graph data
        // This would typically query contacts and group by industries
        $data = [
            'industries' => [],
            'totalContacts' => 0
        ];

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/users/delete",
     *     summary="Delete users",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recordIds"},
     *             @OA\Property(property="recordIds", type="array", @OA\Items(type="string", format="uuid"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function deleteUsers(DeleteUsersRequest $request): JsonResponse
    {
        $recordIds = $request->recordIds;
        
        // Soft delete users
        User::whereIn('id', $recordIds)->update(['is_deleted' => true]);

        return response()->json([
            'message' => 'Success'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/delete/secondary-profile",
     *     summary="Delete secondary profile",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="userId",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Secondary profile not found"
     *     )
     * )
     */
    public function deleteUsersSecondaryProfile(Request $request): JsonResponse
    {
        $userId = $request->query('userId');
        
        $profile = UserProfile::find($userId);
        if (!$profile) {
            return response()->json([
                'message' => 'Secondary profile not found'
            ], 404);
        }

        $profile->delete();

        return response()->json([
            'message' => 'Success'
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/users/update-profile",
     *     summary="Update user profile",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="company_name", type="string", example="Tech Corp"),
     *             @OA\Property(property="position", type="string", example="Software Engineer"),
     *             @OA\Property(property="location", type="string", example="New York, USA"),
     *             @OA\Property(property="bio", type="string", example="Full stack developer"),
     *             @OA\Property(property="industries", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="socials", type="object"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $updateData = [];
        if ($request->has('firstName')) $updateData['first_name'] = $request->firstName;
        if ($request->has('lastName')) $updateData['last_name'] = $request->lastName;
        if ($request->has('phone')) $updateData['phone'] = $request->phone;
        if ($request->has('company_name')) $updateData['company_name'] = $request->company_name;
        if ($request->has('position')) $updateData['position'] = $request->position;
        if ($request->has('location')) $updateData['location'] = $request->location;
        if ($request->has('bio')) $updateData['bio'] = $request->bio;
        if ($request->has('industries')) $updateData['industries'] = $request->industries;
        if ($request->has('socials')) $updateData['social_links'] = $request->socials;
        if ($request->has('city')) $updateData['city'] = $request->city;
        if ($request->has('avatar')) $updateData['avatar'] = $request->avatar;

        $user->update($updateData);

        return response()->json([
            'message' => 'Success',
            'user' => $user
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/users/secondry-profile",
     *     summary="Create secondary profile",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "firstName", "lastName"},
     *             @OA\Property(property="email", type="string", format="email", example="john.secondary@example.com"),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Smith"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="website", type="string", example="https://example.com"),
     *             @OA\Property(property="socials", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function createUserSecondryProfile(CreateSecondaryProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if email already exists in primary users
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'Email already exists in primary users'
            ], 422);
        }

        $profile = $user->userProfiles()->create([
            'email' => $request->email,
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'avatar' => $request->avatar,
            'phone' => $request->phone,
            'website' => $request->website,
            'social_links' => $request->socials,
        ]);

        return response()->json([
            'message' => 'Success',
            'user' => $profile
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/users/socials-preferences",
     *     summary="Update user social preferences",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"socials_preference"},
     *             @OA\Property(property="socials_preference", type="array", @OA\Items(type="string", enum={"linkedin", "instagram", "x", "facebook", "twitter"}))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updateUserSocialsPreferences(UpdateSocialsPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $user->update([
            'socials_preference' => $request->socials_preference
        ]);

        return response()->json([
            'message' => 'Success',
            'data' => [
                'socials_preference' => $user->socials_preference
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/me",
     *     summary="Get current user profile",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('userProfiles');

        return response()->json([
            'message' => 'Success',
            'user' => $user
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/dashboard",
     *     summary="Get User Dashboard Data",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contactsCount", type="integer", example=10),
     *                 @OA\Property(property="sentReferralsCount", type="integer", example=5),
     *                 @OA\Property(property="receivedReferralsCount", type="integer", example=7),
     *                 @OA\Property(property="groupsCreatedCount", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        // TODO: Implement actual dashboard data queries
        // This would typically query contacts, referrals, groups tables
        $data = [
            'contactsCount' => 0,
            'sentReferralsCount' => 0,
            'receivedReferralsCount' => 0,
            'groupsCreatedCount' => 0,
        ];

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/dashboard/graph/location",
     *     summary="Get User Dashboard Contact Location Graph Data",
     *     tags={"users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="cityData", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="contacts", type="object",
     *                     @OA\Property(property="paginatedData", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="totalRecords", type="integer"),
     *                     @OA\Property(property="page", type="integer"),
     *                     @OA\Property(property="limit", type="integer"),
     *                     @OA\Property(property="totalPages", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function getUserLocationGraph(GetUserDashboardLocationRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        
        // TODO: Implement location graph data queries
        // This would typically query contacts and group by location/city
        $cityData = [];
        $contacts = [
            'paginatedData' => [],
            'totalRecords' => 0,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => 0,
        ];

        $data = [
            'cityData' => $cityData,
            'contacts' => $contacts,
        ];

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/industries",
     *     summary="Get industries list",
     *     tags={"users"},
     *     @OA\Response(
     *         response=200,
     *         description="Industries retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="key", type="string"),
     *                 @OA\Property(property="value", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found"
     *     )
     * )
     */
    public function getIndustries(): JsonResponse
    {
        $filePath = storage_path('app/files/industries.json');
        
        if (!File::exists($filePath)) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }

        try {
            $fileContent = File::get($filePath);
            $jsonData = json_decode($fileContent, true);
            
            if (!$jsonData || !isset($jsonData['industries'])) {
                return response()->json([
                    'error' => 'Invalid JSON in file'
                ], 400);
            }

            $resultArray = [];
            foreach ($jsonData['industries'] as $key => $value) {
                $id = md5($key);
                $resultArray[] = [
                    'id' => $id,
                    'key' => $key,
                    'value' => $value
                ];
            }

            return response()->json($resultArray);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid JSON in file'
            ], 400);
        }
    }
}