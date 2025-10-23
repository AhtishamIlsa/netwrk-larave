<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\CreateSecondaryProfileRequest;
use App\Http\Requests\User\UpdateSocialsPreferencesRequest;
use App\Http\Requests\User\DeleteUsersRequest;
use App\Http\Requests\User\GetUserDashboardLocationRequest;
use App\Models\Contact;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\GeocodingService;
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
    protected $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }
    /**
     * @OA\Get(
     *     path="/users/graph/contact-industry",
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
            'statusCode' => 200,
            'message' => 'Success',
            'data' => $data
        ]);
    }

    /**
     * @OA\Post(
     *     path="/users/delete",
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
     *     path="/users/delete/secondary-profile",
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
     *     path="/users/update-profile",
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
        
        // Check if updating secondary profile
        $secondaryUserId = $request->query('secondaryUserId');
        
        $updateData = [];
        if ($request->has('firstName')) $updateData['first_name'] = $request->firstName;
        if ($request->has('lastName')) $updateData['last_name'] = $request->lastName;
        if ($request->has('phone')) $updateData['phone'] = $request->phone;
        if ($request->has('companyName')) $updateData['company_name'] = $request->companyName;
        if ($request->has('position')) $updateData['position'] = $request->position;
        if ($request->has('location')) $updateData['location'] = $request->location;
        if ($request->has('bio')) $updateData['bio'] = $request->bio;
        if ($request->has('industries')) $updateData['industries'] = $request->industries;
        if ($request->has('socials')) $updateData['social_links'] = $request->socials;
        if ($request->has('city')) $updateData['city'] = $request->city;
        if ($request->has('avatar')) $updateData['avatar'] = $request->avatar;
        if ($request->has('website')) $updateData['website'] = $request->website;
        if ($request->has('latitude')) $updateData['latitude'] = $request->latitude;
        if ($request->has('longitude')) $updateData['longitude'] = $request->longitude;

        // Auto-geocode if city is provided but no valid coordinates
        if (isset($updateData['city'])) {
            // Get current or new coordinates
            if ($secondaryUserId) {
                $profile = UserProfile::where('id', $secondaryUserId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
                $currentLat = isset($updateData['latitude']) ? $updateData['latitude'] : $profile->latitude;
                $currentLng = isset($updateData['longitude']) ? $updateData['longitude'] : $profile->longitude;
            } else {
                $currentLat = isset($updateData['latitude']) ? $updateData['latitude'] : $user->latitude;
                $currentLng = isset($updateData['longitude']) ? $updateData['longitude'] : $user->longitude;
            }
            
            // Only geocode if we don't have valid coordinates
            if (!$this->geocodingService->hasValidCoordinates($currentLat, $currentLng)) {
                $geocodeResult = $this->geocodingService->geocode($updateData['city']);
                if ($geocodeResult) {
                    $updateData['latitude'] = $geocodeResult['latitude'];
                    $updateData['longitude'] = $geocodeResult['longitude'];
                }
            }
        }

        // Update secondary profile if secondaryUserId provided
        if ($secondaryUserId) {
            if (!isset($profile)) {
                $profile = UserProfile::where('id', $secondaryUserId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            }
            $profile->update($updateData);
            
            return response()->json([
                'message' => 'Success',
                'user' => $profile
            ]);
        }

        // Update primary user profile
        $user->update($updateData);

        return response()->json([
            'message' => 'Success',
            'user' => $user
        ]);
    }

    /**
     * @OA\Post(
     *     path="/users/secondry-profile",
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

        // Auto-geocode if city provided but no coordinates
        $latitude = $request->latitude ?? null;
        $longitude = $request->longitude ?? null;
        
        if (!empty($request->city) && !$this->geocodingService->hasValidCoordinates($latitude, $longitude)) {
            $geocodeResult = $this->geocodingService->geocode($request->city);
            if ($geocodeResult) {
                $latitude = $geocodeResult['latitude'];
                $longitude = $geocodeResult['longitude'];
            }
        }

        $profile = $user->userProfiles()->create([
            'email' => $request->email,
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'avatar' => $request->avatar,
            'phone' => $request->phone,
            'website' => $request->website,
            'location' => $request->location ?? null,
            'city' => $request->city ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'social_links' => $request->socials ?? [],
            'position' => $request->position ?? null,
            'company_name' => $request->companyName ?? null,
            'industries' => $request->industries ?? [],
            'bio' => $request->bio ?? null,
        ]);

        return response()->json([
            'message' => 'Success',
            'user' => $profile
        ]);
    }

    /**
     * @OA\Post(
     *     path="/users/socials-preferences",
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
     *     path="/users/me",
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

        // Format socials preference to match NestJS structure
        $socialsPreference = [];
        if (is_array($user->socials_preference)) {
            foreach ($user->socials_preference as $social) {
                $socialsPreference[] = [
                    'name' => $social,
                    'visible' => true
                ];
            }
        }

        // Helper function to normalize socials
        $normalizeSocials = function($socialLinks) {
            $defaultSocials = [
                'instagram' => '',
                'linkedin' => '',
                'youtube' => '',
                'snapchat' => '',
                'facebook' => '',
                'tiktok' => '',
                'x' => ''
            ];

            if (empty($socialLinks) || !is_array($socialLinks)) {
                return $defaultSocials;
            }

            // Filter out non-social keys (like numeric indices) and null values
            $filtered = array_filter($socialLinks, function($key) {
                return in_array($key, ['instagram', 'linkedin', 'youtube', 'snapchat', 'facebook', 'tiktok', 'x']);
            }, ARRAY_FILTER_USE_KEY);

            // Merge with defaults and convert nulls to empty strings
            $result = array_merge($defaultSocials, $filtered);
            foreach ($result as $key => $value) {
                $result[$key] = $value ?? '';
            }

            return $result;
        };

        // Create userPrimaryProfile (main user data)
        $userPrimaryProfile = [
            'id' => $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'email' => $user->email,
            'avatar' => $user->avatar ?? '',
            'phone' => $user->phone ?? '',
            'website' => $user->website ?? '',
            'location' => $user->location ?? '',
            'city' => $user->city ?? '',
            'companyName' => $user->company_name ?? '',
            'socials' => $normalizeSocials($user->social_links),
            'bio' => $user->bio ?? '',
            'position' => $user->position ?? '',
            'industries' => $user->industries ?? [],
            'isPrimaryProfile' => true,
            'socialsPreference' => $socialsPreference
        ];

        // Create userSecondaryProfile (secondary profiles)
        $userSecondaryProfile = [];
        foreach ($user->userProfiles as $profile) {
            $userSecondaryProfile[] = [
                'id' => $profile->id,
                'firstName' => $profile->first_name,
                'lastName' => $profile->last_name,
                'email' => $profile->email,
                'avatar' => $profile->avatar ?? '',
                'phone' => $profile->phone ?? '',
                'website' => $profile->website ?? '',
                'location' => $profile->location ?? '',
                'city' => $profile->city ?? '',
                'companyName' => $profile->company_name ?? '',
                'socials' => $normalizeSocials($profile->social_links),
                'bio' => $profile->bio ?? '',
                'position' => $profile->position ?? '',
                'industries' => $profile->industries ?? [],
                'isPrimaryProfile' => false,
                'socialsPreference' => $socialsPreference
            ];
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'success',
            'data' => [
                'user' => [
                    'userPrimaryProfile' => $userPrimaryProfile,
                    'userSecondaryProfile' => $userSecondaryProfile
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/users/dashboard",
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
        
        // Get actual counts from database
        $contactsCount = Contact::where('user_id', $userId)->count();
        
        // TODO: Implement referrals and groups counts when those modules are ready
        $data = [
            'contactsCount' => $contactsCount,
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
     *     path="/users/dashboard/graph/location",
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
        
        // Get map bounds
        $swLat = $request->get('swLat');
        $swLng = $request->get('swLng');
        $neLat = $request->get('neLat');
        $neLng = $request->get('neLng');
        
        // Optional filters
        $industries = $request->get('industries');
        $tags = $request->get('tags');
        $searchCity = $request->get('city'); // Get city from search if provided
        
        // Build query for contacts strictly within bounds (must have valid coords)
        $query = Contact::where('user_id', $userId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '>', $swLat)
            ->where('latitude', '<', $neLat)
            ->where('longitude', '>', $swLng)
            ->where('longitude', '<', $neLng);
        
        // Apply industry filter if provided
        if ($industries) {
            $query->where('industries', 'like', '%' . $industries . '%');
        }
        
        // Apply tags filter if provided
        if ($tags) {
            $query->whereJsonContains('tags', $tags);
        }
        
        // Get total count
        $totalRecords = $query->count();
        
        // Get paginated contacts
        $paginatedContacts = $query
            ->select('id', 'first_name', 'last_name', 'email', 'latitude', 'longitude', 'city')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function($contact) {
                return [
                    'id' => $contact->id,
                    'firstName' => ucwords($contact->first_name),
                    'lastName' => ucwords($contact->last_name),
                    'email' => $contact->email,
                    'latitude' => $contact->latitude,
                    'longitude' => $contact->longitude,
                ];
            });
        
        // Group by city and count for cityData (only contacts within bounds with valid coords)
        $cityDataQuery = Contact::where('user_id', $userId)
            ->whereNotNull('city')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '>', $swLat)
            ->where('latitude', '<', $neLat)
            ->where('longitude', '>', $swLng)
            ->where('longitude', '<', $neLng);
        
        // Apply same filters to cityData
        if ($industries) {
            $cityDataQuery->where('industries', 'like', '%' . $industries . '%');
        }
        if ($tags) {
            $cityDataQuery->whereJsonContains('tags', $tags);
        }
        
        // Get city data with coordinates (use first available coordinates for each city)
        // Only include cities that have valid coordinates for map display
        $cityData = $cityDataQuery
            ->selectRaw('city, COUNT(*) as count, MAX(latitude) as lat, MAX(longitude) as lng')
            ->groupBy('city')
            ->havingRaw('MAX(latitude) IS NOT NULL AND MAX(longitude) IS NOT NULL')
            ->get()
            ->map(function($item) {
                return [
                    'city' => $item->city,
                    'lat' => (float) $item->lat,
                    'lng' => (float) $item->lng,
                    'count' => $item->count,
                ];
            })
            ->toArray();
        
        $contacts = [
            'totalRecords' => $totalRecords,
            'paginatedData' => $paginatedContacts,
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
     *     path="/users/industries",
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

            return response()->json([
                'statusCode' => 200,
                'message' => 'Success',
                'data' => $resultArray
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid JSON in file'
            ], 400);
        }
    }
}