<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="admin",
 *     description="Admin endpoints"
 * )
 */
class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admins/dashboard/stats",
     *     summary="Get admin dashboard statistics",
     *     tags={"admin"},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Dashboard statistics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="totalUsers", type="integer", example=1250),
     *                 @OA\Property(property="totalContacts", type="integer", example=15600),
     *                 @OA\Property(property="totalGroups", type="integer", example=340),
     *                 @OA\Property(property="activeUsers", type="integer", example=1180),
     *                 @OA\Property(property="newUsersThisMonth", type="integer", example=45),
     *                 @OA\Property(property="totalReferrals", type="integer", example=890),
     *                 @OA\Property(property="platformGrowth", type="object",
     *                     @OA\Property(property="users", type="integer", example=12),
     *                     @OA\Property(property="contacts", type="integer", example=8),
     *                     @OA\Property(property="groups", type="integer", example=15)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            // Get total counts
            $totalUsers = User::count();
            $totalContacts = Contact::count();
            $totalGroups = 0; // TODO: Implement when Group model is available
            
            // Get active users (users who have logged in within last 30 days)
            $activeUsers = User::where('updated_at', '>=', now()->subDays(30))->count();
            
            // Get new users this month
            $newUsersThisMonth = User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            // Get total referrals (placeholder - would need referrals table)
            $totalReferrals = 0; // TODO: Implement when referrals table is available
            
            // Calculate growth percentages (compared to last month)
            $lastMonthUsers = User::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count();
            
            $lastMonthContacts = Contact::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count();
            
            $lastMonthGroups = 0; // TODO: Implement when Group model is available
            
            $userGrowth = $lastMonthUsers > 0 ? round((($newUsersThisMonth - $lastMonthUsers) / $lastMonthUsers) * 100, 1) : 0;
            $contactGrowth = $lastMonthContacts > 0 ? round((($totalContacts - $lastMonthContacts) / $lastMonthContacts) * 100, 1) : 0;
            $groupGrowth = $lastMonthGroups > 0 ? round((($totalGroups - $lastMonthGroups) / $lastMonthGroups) * 100, 1) : 0;
            
            $stats = [
                'totalUsers' => $totalUsers,
                'totalContacts' => $totalContacts,
                'totalGroups' => $totalGroups,
                'activeUsers' => $activeUsers,
                'newUsersThisMonth' => $newUsersThisMonth,
                'totalReferrals' => $totalReferrals,
                'platformGrowth' => [
                    'users' => $userGrowth,
                    'contacts' => $contactGrowth,
                    'groups' => $groupGrowth
                ]
            ];
            
            return response()->json([
                'statusCode' => 200,
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to retrieve dashboard statistics',
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admins/dashboard/topUsers",
     *     summary="Get top users for admin dashboard",
     *     tags={"admin"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of top users to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for ranking (week, month, year)",
     *         required=false,
     *         @OA\Schema(type="string", default="month", enum={"week", "month", "year"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Top users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Top users retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="uuid"),
     *                     @OA\Property(property="firstName", type="string", example="John"),
     *                     @OA\Property(property="lastName", type="string", example="Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *                     @OA\Property(property="contactsCount", type="integer", example=150),
     *                     @OA\Property(property="groupsCount", type="integer", example=12),
     *                     @OA\Property(property="referralsCount", type="integer", example=8),
     *                     @OA\Property(property="lastActive", type="string", example="2024-01-15T10:30:00Z"),
     *                     @OA\Property(property="joinedAt", type="string", example="2024-01-01T00:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function getTopUsers(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $period = $request->get('period', 'month');
            
            // Validate period
            if (!in_array($period, ['week', 'month', 'year'])) {
                return response()->json([
                    'statusCode' => 400,
                    'message' => 'Invalid period. Must be week, month, or year',
                    'data' => null
                ], 400);
            }
            
            // Calculate date range based on period
            $dateRange = match($period) {
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'year' => now()->subYear(),
                default => now()->subMonth()
            };
            
            // Get top users based on activity (contacts created)
            $topUsers = User::select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.avatar',
                'users.updated_at as last_active',
                'users.created_at as joined_at',
                DB::raw('COUNT(DISTINCT contacts.id) as contacts_count'),
                DB::raw('0 as groups_count'), // TODO: Implement when Group model is available
                DB::raw('0 as referrals_count') // Placeholder until referrals table is available
            ])
            ->leftJoin('contacts', 'users.id', '=', 'contacts.user_id')
            ->where('users.created_at', '>=', $dateRange)
            ->where('users.is_deleted', false)
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.avatar', 'users.updated_at', 'users.created_at')
            ->orderByDesc('contacts_count')
            ->orderByDesc('users.updated_at')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? '',
                    'contactsCount' => (int) $user->contacts_count,
                    'groupsCount' => (int) $user->groups_count,
                    'referralsCount' => (int) $user->referrals_count,
                    'lastActive' => $user->last_active->toISOString(),
                    'joinedAt' => $user->joined_at->toISOString()
                ];
            });
            
            return response()->json([
                'statusCode' => 200,
                'message' => 'Top users retrieved successfully',
                'data' => $topUsers
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to retrieve top users',
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admins/dashboard/topGroups",
     *     summary="Get top groups for admin dashboard",
     *     tags={"admin"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of top groups to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Top groups retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Top groups retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getTopGroups(Request $request): JsonResponse
    {
        try {
            // TODO: Implement when Group model is available
            return response()->json([
                'statusCode' => 200,
                'message' => 'Top groups retrieved successfully',
                'data' => []
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to retrieve top groups',
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admins/users",
     *     summary="Get all users for admin management",
     *     tags={"admin"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of users per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for name or email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="users", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=1250),
     *                     @OA\Property(property="last_page", type="integer", example=63)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $search = $request->get('search');
            
            $query = User::select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.avatar',
                'users.role',
                'users.created_at',
                'users.updated_at',
                'users.is_deleted',
                DB::raw('COUNT(DISTINCT contacts.id) as contacts_count'),
                DB::raw('COUNT(DISTINCT groups.id) as groups_count')
            ])
            ->leftJoin('contacts', 'users.id', '=', 'contacts.user_id')
            ->leftJoin('groups', 'users.id', '=', 'groups.user_id')
            ->where('users.is_deleted', false);
            
            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.first_name', 'like', "%{$search}%")
                      ->orWhere('users.last_name', 'like', "%{$search}%")
                      ->orWhere('users.email', 'like', "%{$search}%");
                });
            }
            
            $users = $query->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.avatar', 'users.role', 'users.created_at', 'users.updated_at', 'users.is_deleted')
                ->orderBy('users.created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);
            
            $formattedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? '',
                    'role' => $user->role,
                    'contactsCount' => (int) $user->contacts_count,
                    'groupsCount' => (int) $user->groups_count,
                    'createdAt' => $user->created_at->toISOString(),
                    'lastActive' => $user->updated_at->toISOString(),
                    'isActive' => !$user->is_deleted
                ];
            });
            
            return response()->json([
                'statusCode' => 200,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'users' => $formattedUsers,
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'last_page' => $users->lastPage()
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to retrieve users',
                'data' => null
            ], 500);
        }
    }
}
