<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\GeocodingService;
use App\Jobs\GeocodeContactsJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="contacts",
 *     description="Contact management endpoints"
 * )
 */
class ContactsController extends Controller
{
    protected $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    /**
     * @OA\Get(
     *     path="/contacts",
     *     summary="Get contacts list",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at:desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="totalRecords", type="integer", example=100),
     *             @OA\Property(property="totalPages", type="integer", example=10),
     *             @OA\Property(property="currentPage", type="integer", example=1),
     *             @OA\Property(property="limit", type="integer", example=10),
     *             @OA\Property(property="count", type="integer", example=10),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contacts", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getContacts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $filter = $request->get('filter', '');
        $sort = $request->get('sort', 'created_at:desc');

        [$sortField, $sortDir] = explode(':', $sort . ':asc');
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'asc';

        $query = Contact::where('user_id', $userId);

        // Apply filter
        if ($filter) {
            $query->where(function ($q) use ($filter) {
                $q->where('search_index', 'ILIKE', "%{$filter}%")
                    ->orWhere('email', 'ILIKE', "%{$filter}%")
                    ->orWhere('first_name', 'ILIKE', "%{$filter}%")
                    ->orWhere('last_name', 'ILIKE', "%{$filter}%");
            });
        }

        $total = $query->count();

        $contacts = $query
            ->orderBy($sortField, $sortDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($contact) {
                return $this->formatContact($contact);
            });

        $totalPages = ceil($total / $limit);

        return response()->json([
            'status' => 'success',
            'totalRecords' => $total,
            'totalPages' => $totalPages,
            'currentPage' => (int)$page,
            'limit' => (int)$limit,
            'count' => $contacts->count(),
            'data' => [
                'contacts' => $contacts
            ],
            'message' => 'Success'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/contacts/indirect-contacts",
     *     summary="Get indirect contacts count",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Indirect contacts fetched successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="indirectContacts", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getIndirectContacts(Request $request): JsonResponse
    {
        $userEmail = $request->user()->email;
        
        // TODO: Implement referral/introduction logic
        // For now, return 0 as placeholder
        // In the future, this will count contacts from connected users via referrals
        $indirectContacts = 0;

        return response()->json([
            'statusCode' => 200,
            'message' => 'Indirect contacts fetched successfully',
            'data' => [
                'indirectContacts' => $indirectContacts
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/contacts/graph/{year}",
     *     summary="Get contacts growth chart data",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=2025)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="comulativeData", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="date", type="string", example="January"),
     *                     @OA\Property(property="contacts_joined", type="integer", example=0)
     *                 )),
     *                 @OA\Property(property="total", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getContactsChartData(Request $request, $year): JsonResponse
    {
        $userId = $request->user()->id;
        
        // Get contacts created in the specified year
        $contacts = Contact::where('user_id', $userId)
            ->whereYear('created_at', $year)
            ->selectRaw('EXTRACT(MONTH FROM created_at) as month')
            ->get();

        // Initialize data for all 12 months
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $data = array_map(function($month) {
            return [
                'date' => $month,
                'contacts_joined' => 0
            ];
        }, $months);

        // Count contacts per month
        $total = 0;
        foreach ($contacts as $contact) {
            $monthIndex = (int)$contact->month - 1;
            $data[$monthIndex]['contacts_joined'] += 1;
            $total += 1;
        }

        // Calculate cumulative data
        $comulativeData = [];
        $cumulative = 0;
        foreach ($data as $monthData) {
            $cumulative += $monthData['contacts_joined'];
            $comulativeData[] = [
                'date' => $monthData['date'],
                'contacts_joined' => $cumulative
            ];
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Success',
            'data' => [
                'comulativeData' => $comulativeData,
                'total' => $total
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/contacts/create-contact",
     *     summary="Create a new contact",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"firstName", "lastName"},
     *             @OA\Property(property="firstName", type="string"),
     *             @OA\Property(property="lastName", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="position", type="string"),
     *             @OA\Property(property="company", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="industries", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="socials", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contact created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     )
     * )
     */
    public function createContact(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        // Get query parameters (used for batch operations)
        $uploadSessionId = $request->query('uploadSessionId');
        $totalCountofContacts = $request->query('totalCountofContacts', 1);
        
        // Get the JSON payload - frontend sends array directly
        $rawData = $request->json()->all();
        
        // If the payload is an array at root level, use it directly
        // Otherwise, check if it's a single contact object
        if (is_array($rawData) && !empty($rawData)) {
            // Check if it's an array of contacts (numeric keys) or a single contact (string keys)
            $firstKey = array_key_first($rawData);
            if (is_numeric($firstKey)) {
                // It's an array of contacts
                $contactsData = $rawData;
            } else {
                // It's a single contact object
                $contactsData = [$rawData];
            }
        } else {
            $contactsData = [];
        }

        $createdContacts = [];
        $totalRecords = Contact::where('user_id', $userId)->count();

        foreach ($contactsData as $contactData) {
            // Normalize array fields to allow empty entries without failing validation
            if (isset($contactData['tags'])) {
                if (is_string($contactData['tags'])) {
                    $decoded = json_decode($contactData['tags'], true);
                    if (is_array($decoded)) {
                        $contactData['tags'] = $decoded;
                    } else {
                        $contactData['tags'] = array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string)$contactData['tags']))));
                    }
                }
                if (is_array($contactData['tags'])) {
                    $contactData['tags'] = array_values(array_filter(array_map(function ($v) {
                        return is_string($v) ? trim($v) : '';
                    }, $contactData['tags']), function ($v) { return $v !== ''; }));
                }
            }
            if (isset($contactData['industries'])) {
                if (is_string($contactData['industries'])) {
                    $decoded = json_decode($contactData['industries'], true);
                    if (is_array($decoded)) {
                        $contactData['industries'] = $decoded;
                    } else {
                        $contactData['industries'] = array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string)$contactData['industries']))));
                    }
                }
                if (is_array($contactData['industries'])) {
                    $contactData['industries'] = array_values(array_filter(array_map(function ($v) {
                        return is_string($v) ? trim($v) : '';
                    }, $contactData['industries']), function ($v) { return $v !== ''; }));
                }
            }

            $validator = Validator::make($contactData, [
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'position' => 'nullable|string|max:255',
                'company' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'workPhone' => 'nullable|string|max:255',
                'homePhone' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'timezone' => 'nullable|string|max:100',
                'birthday' => 'nullable|string',
                'notes' => 'nullable|string',
                'tags' => 'nullable|array',
                'tags.*' => 'nullable|string|max:50',
                'industries' => 'nullable|array',
                'industries.*' => 'nullable|string|max:50',
                'socials' => 'nullable|array',
                'title' => 'nullable|string|max:255',
                'role' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $createdContacts[] = [
                    'success' => false,
                    'message' => 'Validation error: ' . $validator->errors()->first(),
                    'email' => $contactData['email'] ?? null
                ];
                continue;
            }

            $data = $validator->validated();

            // Check if contact with same email already exists
            if (!empty($data['email'])) {
                $exists = Contact::where('user_id', $userId)
                    ->where('email', strtolower($data['email']))
                    ->exists();

                if ($exists) {
                    $createdContacts[] = [
                        'success' => false,
                        'message' => "A contact with email {$data['email']} already exists.",
                        'email' => $data['email']
                    ];
                    continue;
                }
            }

            // Auto-geocode if city provided but no coordinates
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;
            
            if (!empty($data['city']) && !$this->geocodingService->hasValidCoordinates($latitude, $longitude)) {
                $geocodeResult = $this->geocodingService->geocode($data['city']);
                if ($geocodeResult) {
                    $latitude = $geocodeResult['latitude'];
                    $longitude = $geocodeResult['longitude'];
                }
            }

            $contact = Contact::create([
                'user_id' => $userId,
                'first_name' => strtolower(trim($data['firstName'])),
                'last_name' => strtolower(trim($data['lastName'])),
                'email' => isset($data['email']) ? strtolower(trim($data['email'])) : null,
                'position' => $data['position'] ?? null,
                'company_name' => $data['company'] ?? null,
                'phone' => $data['phone'] ?? null,
                'work_phone' => $data['workPhone'] ?? null,
                'home_phone' => $data['homePhone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => $data['timezone'] ?? null,
                'birthday' => $data['birthday'] ?? null,
                'notes' => $data['notes'] ?? null,
                'tags' => $data['tags'] ?? [],
                'industries' => $data['industries'] ?? [],
                'socials' => $data['socials'] ?? [],
                'title' => $data['title'] ?? null,
                'role' => $data['role'] ?? null,
            ]);

            $totalRecords++;
            
            $formattedContact = $this->formatContact($contact);
            $formattedContact['success'] = true;
            $createdContacts[] = $formattedContact;
        }

        return response()->json([
            'status' => 'success',
            'totalRecords' => $totalRecords,
            'message' => 'Contact created successfully',
            'data' => [
                'contacts' => $createdContacts
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/contacts/get-contact/{contactId}",
     *     summary="Get a single contact",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="contactId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contact not found"
     *     )
     * )
     */
    public function getSingleContact(Request $request, $contactId): JsonResponse
    {
        $userId = $request->user()->id;

        $contact = Contact::where('user_id', $userId)
            ->where('id', $contactId)
            ->first();

        if (!$contact) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Contact not found',
                'data' => null
            ], 404);
        }

        // Return contact data directly (matching NestJS format)
        // Frontend expects response.data to be the contact object itself
        return response()->json($this->formatContact($contact));
    }

    /**
     * @OA\Patch(
     *     path="/contacts/update-contact/{contactId}",
     *     summary="Update a contact",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="contactId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="firstName", type="string"),
     *             @OA\Property(property="lastName", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="position", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contact not found"
     *     )
     * )
     */
    public function updateContact(Request $request, $contactId): JsonResponse
    {
        $userId = $request->user()->id;

        $contact = Contact::where('user_id', $userId)
            ->where('id', $contactId)
            ->first();

        if (!$contact) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Contact not found',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'firstName' => 'nullable|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'position' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'workPhone' => 'nullable|string|max:255',
            'homePhone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'timezone' => 'nullable|string|max:100',
            'birthday' => 'nullable|string',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'industries' => 'nullable|array',
            'socials' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $data = $validator->validated();
        $updateData = [];

        if (isset($data['firstName'])) $updateData['first_name'] = strtolower(trim($data['firstName']));
        if (isset($data['lastName'])) $updateData['last_name'] = strtolower(trim($data['lastName']));
        if (isset($data['email'])) $updateData['email'] = strtolower(trim($data['email']));
        if (isset($data['position'])) $updateData['position'] = $data['position'];
        if (isset($data['company'])) $updateData['company_name'] = $data['company'];
        if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
        if (isset($data['workPhone'])) $updateData['work_phone'] = $data['workPhone'];
        if (isset($data['homePhone'])) $updateData['home_phone'] = $data['homePhone'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];
        if (isset($data['city'])) $updateData['city'] = $data['city'];
        if (isset($data['latitude'])) $updateData['latitude'] = $data['latitude'];
        if (isset($data['longitude'])) $updateData['longitude'] = $data['longitude'];
        if (isset($data['timezone'])) $updateData['timezone'] = $data['timezone'];
        if (isset($data['birthday'])) $updateData['birthday'] = $data['birthday'];
        if (isset($data['notes'])) $updateData['notes'] = $data['notes'];
        if (isset($data['tags'])) $updateData['tags'] = $data['tags'];
        if (isset($data['industries'])) $updateData['industries'] = $data['industries'];
        if (isset($data['socials'])) $updateData['socials'] = $data['socials'];
        if (isset($data['title'])) $updateData['title'] = $data['title'];
        if (isset($data['role'])) $updateData['role'] = $data['role'];

        // Auto-geocode if city changed but no coordinates provided
        if (isset($updateData['city'])) {
            $currentLat = isset($updateData['latitude']) ? $updateData['latitude'] : $contact->latitude;
            $currentLng = isset($updateData['longitude']) ? $updateData['longitude'] : $contact->longitude;
            
            // Only geocode if we don't have valid coordinates
            if (!$this->geocodingService->hasValidCoordinates($currentLat, $currentLng)) {
                $geocodeResult = $this->geocodingService->geocode($updateData['city']);
                if ($geocodeResult) {
                    $updateData['latitude'] = $geocodeResult['latitude'];
                    $updateData['longitude'] = $geocodeResult['longitude'];
                }
            }
        }

        $contact->update($updateData);

        return response()->json([
            'statusCode' => 200,
            'message' => 'Contact updated successfully',
            'data' => $this->formatContact($contact->fresh())
        ]);
    }

    /**
     * @OA\Post(
     *     path="/contacts/delete",
     *     summary="Delete contacts",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recordIds"},
     *             @OA\Property(property="recordIds", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts deleted successfully"
     *     )
     * )
     */
    public function deleteContacts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recordIds' => 'required|array',
            'recordIds.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $userId = $request->user()->id;
        $recordIds = $request->recordIds;

        Contact::where('user_id', $userId)
            ->whereIn('id', $recordIds)
            ->delete();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Contacts deleted successfully',
            'data' => null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/contacts/import-csv",
     *     summary="Import contacts from CSV",
     *     tags={"contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="CSV file to import"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV imported",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Import completed"),
     *             @OA\Property(property="totalRecords", type="integer", example=42),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="totalRows", type="integer", example=10),
     *                     @OA\Property(property="created", type="integer", example=8),
     *                     @OA\Property(property="skipped", type="integer", example=2)
     *                 ),
     *                 @OA\Property(property="results", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function importCsv(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:20480', // 20MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $userId = $request->user()->id;
        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Unable to read uploaded file',
                'data' => null
            ], 400);
        }

        $header = null;
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(function($h) { return strtolower(trim($h)); }, $data);
                continue;
            }
            // Build associative row by header
            $row = [];
            foreach ($data as $i => $value) {
                $key = $header[$i] ?? 'col_' . $i;
                $row[$key] = $value;
            }
            $rows[] = $row;
        }
        fclose($handle);

        $results = [];
        $created = 0;
        $skipped = 0;
        $totalRecords = Contact::where('user_id', $userId)->count();

        foreach ($rows as $row) {
            // Map flexible headers to expected API keys
            $mapped = [
                'firstName' => $row['firstname'] ?? $row['first_name'] ?? $row['first'] ?? null,
                'lastName' => $row['lastname'] ?? $row['last_name'] ?? $row['last'] ?? null,
                'email' => $row['email'] ?? null,
                'position' => $row['position'] ?? null,
                'company' => $row['company'] ?? $row['company_name'] ?? null,
                'phone' => $row['phone'] ?? null,
                'workPhone' => $row['workphone'] ?? $row['work_phone'] ?? null,
                'homePhone' => $row['homephone'] ?? $row['home_phone'] ?? null,
                'address' => $row['address'] ?? null,
                'additionalAddresses' => $row['additionaladdresses'] ?? $row['additional_addresses'] ?? null,
                'city' => $row['city'] ?? null,
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'timezone' => $row['timezone'] ?? null,
                'birthday' => $row['birthday'] ?? null,
                'notes' => $row['notes'] ?? null,
                'title' => $row['title'] ?? null,
                'role' => $row['role'] ?? null,
                'websiteUrl' => $row['websiteurl'] ?? $row['website_url'] ?? null,
                // optional hints for geocoding
                'state' => $row['state'] ?? $row['state_code'] ?? $row['province'] ?? null,
                'country' => $row['country'] ?? $row['country_code'] ?? null,
            ];

            // tags and industries could be semicolon/comma separated or JSON arrays; allow empty strings safely
            if (isset($row['tags'])) {
                $t = $row['tags'];
                if (is_string($t)) {
                    $decoded = json_decode($t, true);
                    if (is_array($decoded)) {
                        $t = $decoded;
                    } else {
                        $t = preg_split('/[;,]/', (string)$t);
                    }
                }
                if (is_array($t)) {
                    $mapped['tags'] = array_values(array_filter(array_map(function ($v) {
                        return is_string($v) ? trim($v) : '';
                    }, $t), function ($v) { return $v !== ''; }));
                }
            }
            if (isset($row['industries'])) {
                $ind = $row['industries'];
                if (is_string($ind)) {
                    $decoded = json_decode($ind, true);
                    if (is_array($decoded)) {
                        $ind = $decoded;
                    } else {
                        $ind = preg_split('/[;,]/', (string)$ind);
                    }
                }
                if (is_array($ind)) {
                    $mapped['industries'] = array_values(array_filter(array_map(function ($v) {
                        return is_string($v) ? trim($v) : '';
                    }, $ind), function ($v) { return $v !== ''; }));
                }
            }
            // socials may be JSON string
            if (!empty($row['socials'])) {
                $decoded = json_decode((string)$row['socials'], true);
                if (is_array($decoded)) {
                    $mapped['socials'] = $decoded;
                }
            }

            // Basic validation
            if (empty($mapped['firstName']) || empty($mapped['lastName'])) {
                $results[] = [
                    'success' => false,
                    'message' => 'Missing firstName/lastName',
                    'email' => $mapped['email'] ?? null,
                ];
                $skipped++;
                continue;
            }

            // Duplication by email for this user
            if (!empty($mapped['email'])) {
                $exists = Contact::where('user_id', $userId)
                    ->where('email', strtolower($mapped['email']))
                    ->exists();
                if ($exists) {
                    $results[] = [
                        'success' => false,
                        'message' => 'Duplicate email',
                        'email' => $mapped['email']
                    ];
                    $skipped++;
                    continue;
                }
            }

            // Normalize numeric fields: treat empty strings as null, cast when present
            $normLat = $mapped['latitude'] ?? null;
            if (is_string($normLat) && trim($normLat) === '') { $normLat = null; }
            elseif (!is_null($normLat)) { $normLat = (float) $normLat; }

            $normLng = $mapped['longitude'] ?? null;
            if (is_string($normLng) && trim($normLng) === '') { $normLng = null; }
            elseif (!is_null($normLng)) { $normLng = (float) $normLng; }

            // If lat/lng still missing but city provided, try resolve from cities cache (and fallback via service)
            if (!empty($mapped['city']) && !$this->geocodingService->hasValidCoordinates($normLat, $normLng)) {
                $geo = $this->geocodingService->geocode($mapped['city'], $mapped['state'] ?? null, $mapped['country'] ?? null);
                if ($geo && isset($geo['latitude'], $geo['longitude'])) {
                    $normLat = (float) $geo['latitude'];
                    $normLng = (float) $geo['longitude'];
                }
            }

            $contact = Contact::create([
                'user_id' => $userId,
                'first_name' => strtolower(trim($mapped['firstName'])),
                'last_name' => strtolower(trim($mapped['lastName'])),
                'email' => isset($mapped['email']) ? strtolower(trim($mapped['email'])) : null,
                'position' => $mapped['position'] ?? null,
                'company_name' => $mapped['company'] ?? null,
                'phone' => $mapped['phone'] ?? null,
                'work_phone' => $mapped['workPhone'] ?? null,
                'home_phone' => $mapped['homePhone'] ?? null,
                'address' => $mapped['address'] ?? null,
                'additional_addresses' => $mapped['additionalAddresses'] ?? null,
                'city' => $mapped['city'] ?? null,
                'latitude' => $normLat,
                'longitude' => $normLng,
                'timezone' => $mapped['timezone'] ?? null,
                'birthday' => $mapped['birthday'] ?? null,
                'notes' => $mapped['notes'] ?? null,
                'tags' => $mapped['tags'] ?? [],
                'industries' => $mapped['industries'] ?? [],
                'socials' => $mapped['socials'] ?? [],
                'title' => $mapped['title'] ?? null,
                'role' => $mapped['role'] ?? null,
                'website_url' => $mapped['websiteUrl'] ?? null,
            ]);

            $totalRecords++;
            // Return concise per-row result
            $results[] = [
                'success' => true,
                'id' => $contact->id,
                'email' => $contact->email,
                'city' => $contact->city,
                'latitude' => $contact->latitude,
                'longitude' => $contact->longitude,
            ];
            $created++;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Import completed',
            'totalRecords' => $totalRecords,
            'data' => [
                'summary' => [
                    'totalRows' => count($rows),
                    'created' => $created,
                    'skipped' => $skipped,
                ],
                'results' => $results,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/contacts/import-csv-bulk",
     *     summary="Import contacts from CSV file (Bulk Optimized)",
     *     description="Import contacts from CSV file using bulk operations for better performance with large datasets",
     *     operationId="importCsvBulk",
     *     tags={"Contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary", description="CSV file to import")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Import completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Bulk import completed"),
     *             @OA\Property(property="totalRecords", type="integer", example=1000),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="totalRows", type="integer", example=1000),
     *                     @OA\Property(property="created", type="integer", example=950),
     *                     @OA\Property(property="skipped", type="integer", example=50),
     *                     @OA\Property(property="processingTime", type="string", example="2.5s")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function importCsvBulk(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:20480', // 20MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $userId = $request->user()->id;
        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Unable to read uploaded file',
                'data' => null
            ], 400);
        }

        // Parse CSV
        $header = null;
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(function($h) { return strtolower(trim($h)); }, $data);
                continue;
            }
            $row = [];
            foreach ($data as $i => $value) {
                $key = $header[$i] ?? 'col_' . $i;
                $row[$key] = $value;
            }
            $rows[] = $row;
        }
        fclose($handle);

        // Process all rows and prepare bulk data
        $validContacts = [];
        $skippedContacts = [];
        $emailsToCheck = [];

        foreach ($rows as $index => $row) {
            // Map flexible headers to expected API keys
            $mapped = [
                'firstName' => $row['firstname'] ?? $row['first_name'] ?? $row['first'] ?? null,
                'lastName' => $row['lastname'] ?? $row['last_name'] ?? $row['last'] ?? null,
                'email' => $row['email'] ?? null,
                'position' => $row['position'] ?? null,
                'company' => $row['company'] ?? $row['company_name'] ?? null,
                'phone' => $row['phone'] ?? null,
                'workPhone' => $row['workphone'] ?? $row['work_phone'] ?? null,
                'homePhone' => $row['homephone'] ?? $row['home_phone'] ?? null,
                'address' => $row['address'] ?? null,
                'additionalAddresses' => $row['additionaladdresses'] ?? $row['additional_addresses'] ?? null,
                'city' => $row['city'] ?? null,
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'timezone' => $row['timezone'] ?? null,
                'birthday' => $row['birthday'] ?? null,
                'notes' => $row['notes'] ?? null,
                'title' => $row['title'] ?? null,
                'role' => $row['role'] ?? null,
                'websiteUrl' => $row['websiteurl'] ?? $row['website_url'] ?? null,
                'state' => $row['state'] ?? $row['state_code'] ?? $row['province'] ?? null,
                'country' => $row['country'] ?? $row['country_code'] ?? null,
            ];

            // Process tags and industries
            if (isset($row['tags'])) {
                $t = $row['tags'];
                if (is_string($t)) {
                    $decoded = json_decode($t, true);
                    if (is_array($decoded)) {
                        $t = $decoded;
                    } else {
                        $t = preg_split('/[;,]/', (string)$t);
                    }
                }
                if (is_array($t)) {
                    $mapped['tags'] = array_values(array_filter(array_map(function ($v) {
                        return is_string($v) ? trim($v) : '';
                    }, $t), function ($v) { return $v !== ''; }));
                }
            }
            if (isset($row['industries'])) {
                $ind = $row['industries'];
                if (is_string($ind)) {
                    $decoded = json_decode($ind, true);
                    if (is_array($decoded)) {
                        $ind = $decoded;
                    } else {
                        $ind = preg_split('/[;,]/', (string)$ind);
                    }
                }
                if (is_array($ind)) {
                    $mapped['industries'] = array_values(array_filter(array_map(function ($v) {
                        return is_string($v) ? trim($v) : '';
                    }, $ind), function ($v) { return $v !== ''; }));
                }
            }
            if (!empty($row['socials'])) {
                $decoded = json_decode((string)$row['socials'], true);
                if (is_array($decoded)) {
                    $mapped['socials'] = $decoded;
                }
            }

            // Basic validation
            if (empty($mapped['firstName']) || empty($mapped['lastName'])) {
                $skippedContacts[] = [
                    'row' => $index + 1,
                    'reason' => 'Missing firstName/lastName',
                    'email' => $mapped['email'] ?? null,
                ];
                continue;
            }

            // Collect emails for bulk duplicate check
            if (!empty($mapped['email'])) {
                $emailsToCheck[] = strtolower(trim($mapped['email']));
            }

            // Normalize coordinates
            $normLat = $mapped['latitude'] ?? null;
            if (is_string($normLat) && trim($normLat) === '') { $normLat = null; }
            elseif (!is_null($normLat)) { $normLat = (float) $normLat; }

            $normLng = $mapped['longitude'] ?? null;
            if (is_string($normLng) && trim($normLng) === '') { $normLng = null; }
            elseif (!is_null($normLng)) { $normLng = (float) $normLng; }

            $validContacts[] = [
                'row_index' => $index,
                'data' => $mapped,
                'latitude' => $normLat,
                'longitude' => $normLng,
            ];
        }

        // Bulk check for duplicates
        $existingEmails = [];
        if (!empty($emailsToCheck)) {
            $existingEmails = Contact::where('user_id', $userId)
                ->whereIn('email', $emailsToCheck)
                ->pluck('email')
                ->toArray();
        }

        // Filter out duplicates and prepare final data
        $contactsToInsert = [];
        $citiesToGeocode = [];

        foreach ($validContacts as $contact) {
            $email = isset($contact['data']['email']) ? strtolower(trim($contact['data']['email'])) : null;
            
            // Check for duplicates
            if ($email && in_array($email, $existingEmails)) {
                $skippedContacts[] = [
                    'row' => $contact['row_index'] + 1,
                    'reason' => 'Duplicate email',
                    'email' => $email,
                ];
                continue;
            }

            // Check if geocoding is needed
            if (!empty($contact['data']['city']) && !$this->geocodingService->hasValidCoordinates($contact['latitude'], $contact['longitude'])) {
                $citiesToGeocode[] = [
                    'contact_index' => count($contactsToInsert),
                    'city' => $contact['data']['city'],
                    'state' => $contact['data']['state'] ?? null,
                    'country' => $contact['data']['country'] ?? null,
                ];
            }

            // Prepare contact data for bulk insert
            $contactsToInsert[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'first_name' => strtolower(trim($contact['data']['firstName'])),
                'last_name' => strtolower(trim($contact['data']['lastName'])),
                'email' => $email,
                'position' => $contact['data']['position'] ?? null,
                'company_name' => $contact['data']['company'] ?? null,
                'phone' => $contact['data']['phone'] ?? null,
                'work_phone' => $contact['data']['workPhone'] ?? null,
                'home_phone' => $contact['data']['homePhone'] ?? null,
                'address' => $contact['data']['address'] ?? null,
                'additional_addresses' => $contact['data']['additionalAddresses'] ?? null,
                'city' => $contact['data']['city'] ?? null,
                'latitude' => $contact['latitude'],
                'longitude' => $contact['longitude'],
                'timezone' => $contact['data']['timezone'] ?? null,
                'birthday' => $contact['data']['birthday'] ?? null,
                'notes' => $contact['data']['notes'] ?? null,
                'tags' => json_encode($contact['data']['tags'] ?? []),
                'industries' => json_encode($contact['data']['industries'] ?? []),
                'socials' => json_encode($contact['data']['socials'] ?? []),
                'title' => $contact['data']['title'] ?? null,
                'role' => $contact['data']['role'] ?? null,
                'website_url' => $contact['data']['websiteUrl'] ?? null,
                'search_index' => strtolower(trim($contact['data']['firstName'] . ' ' . $contact['data']['lastName'])),
                'on_platform' => false,
                'has_sync' => false,
                'needs_sync' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch geocoding for cities that need it
        $geocodingResults = [];
        if (!empty($citiesToGeocode)) {
            $uniqueCities = collect($citiesToGeocode)->unique(function ($item) {
                return $item['city'] . '|' . ($item['state'] ?? '') . '|' . ($item['country'] ?? '');
            });

            foreach ($uniqueCities as $cityData) {
                $geo = $this->geocodingService->geocode($cityData['city'], $cityData['state'], $cityData['country']);
                if ($geo && isset($geo['latitude'], $geo['longitude'])) {
                    $geocodingResults[$cityData['city'] . '|' . ($cityData['state'] ?? '') . '|' . ($cityData['country'] ?? '')] = [
                        'latitude' => (float) $geo['latitude'],
                        'longitude' => (float) $geo['longitude'],
                    ];
                }
            }

            // Apply geocoding results to contacts
            foreach ($citiesToGeocode as $geoData) {
                $key = $geoData['city'] . '|' . ($geoData['state'] ?? '') . '|' . ($geoData['country'] ?? '');
                if (isset($geocodingResults[$key])) {
                    $contactsToInsert[$geoData['contact_index']]['latitude'] = $geocodingResults[$key]['latitude'];
                    $contactsToInsert[$geoData['contact_index']]['longitude'] = $geocodingResults[$key]['longitude'];
                }
            }
        }

        // Bulk insert contacts
        $created = 0;
        if (!empty($contactsToInsert)) {
            // Process in chunks to avoid memory issues
            $chunks = array_chunk($contactsToInsert, 1000);
            foreach ($chunks as $chunk) {
                Contact::insert($chunk);
                $created += count($chunk);
            }
        }

        $totalRecords = Contact::where('user_id', $userId)->count();
        $processingTime = round(microtime(true) - $startTime, 2);

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk import completed',
            'totalRecords' => $totalRecords,
            'data' => [
                'summary' => [
                    'totalRows' => count($rows),
                    'created' => $created,
                    'skipped' => count($skippedContacts),
                    'processingTime' => $processingTime . 's',
                ],
                'skippedContacts' => $skippedContacts,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/contacts/import-csv-copy",
     *     summary="Import contacts from CSV file (PostgreSQL COPY Optimized)",
     *     description="Import contacts using PostgreSQL COPY command with temporary table and ON CONFLICT upsert for maximum performance. CSV must have headers: user_id, first_name, last_name, email, position, company_name, phone, work_phone, home_phone, address, additional_addresses, city, latitude, longitude, timezone, birthday, notes, tags, industries, socials, title, role, website_url",
     *     operationId="importCsvCopy",
     *     tags={"Contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary", description="CSV file to import")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Import completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="COPY import completed"),
     *             @OA\Property(property="totalRecords", type="integer", example=10000),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="totalRows", type="integer", example=10000),
     *                     @OA\Property(property="created", type="integer", example=9500),
     *                     @OA\Property(property="updated", type="integer", example=500),
     *                     @OA\Property(property="processingTime", type="string", example="1.2s")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function importCsvCopy(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:20480', // 20MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $userId = $request->user()->id;
        $file = $request->file('file');

        // Generate unique temporary table name
        $tempTableName = 'temp_contacts_' . uniqid();

        try {
            // Use database transaction for safety
            DB::transaction(function () use ($tempTableName, $file, $userId) {
                // Create temporary table with same structure as contacts
                DB::statement("
                    CREATE TEMPORARY TABLE {$tempTableName} (
                        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                        user_id UUID NOT NULL,
                        first_name VARCHAR(255),
                        last_name VARCHAR(255),
                        email VARCHAR(255),
                        position VARCHAR(255),
                        company_name VARCHAR(255),
                        phone VARCHAR(255),
                        work_phone VARCHAR(255),
                        home_phone VARCHAR(255),
                        address TEXT,
                        additional_addresses TEXT,
                        city VARCHAR(255),
                        latitude DECIMAL(10,8),
                        longitude DECIMAL(11,8),
                        timezone VARCHAR(255),
                        birthday DATE,
                        notes TEXT,
                        tags JSONB,
                        industries JSONB,
                        socials JSONB,
                        title VARCHAR(255),
                        role VARCHAR(255),
                        website_url TEXT,
                        search_index TEXT,
                        on_platform BOOLEAN DEFAULT false,
                        has_sync BOOLEAN DEFAULT false,
                        needs_sync BOOLEAN DEFAULT false,
                        created_at TIMESTAMP DEFAULT NOW(),
                        updated_at TIMESTAMP DEFAULT NOW()
                    )
                ");

                // Parse CSV and insert data using Laravel's file handling
                $handle = fopen($file->getRealPath(), 'r');
                if ($handle === false) {
                    throw new \Exception('Unable to read uploaded file');
                }

                $header = null;
                $batchData = [];
                $batchSize = 1000; // Process in batches

                while (($data = fgetcsv($handle)) !== false) {
                    if ($header === null) {
                        $header = array_map(function($h) { return strtolower(trim($h)); }, $data);
                        continue;
                    }

                    // Build associative row by header
                    $row = [];
                    foreach ($data as $i => $value) {
                        $key = $header[$i] ?? 'col_' . $i;
                        $row[$key] = $value;
                    }

                    // Map CSV columns to database columns
                    $mappedRow = [
                        'user_id' => $userId,
                        'first_name' => $row['first_name'] ?? $row['firstname'] ?? $row['first'] ?? null,
                        'last_name' => $row['last_name'] ?? $row['lastname'] ?? $row['last'] ?? null,
                        'email' => $row['email'] ?? null,
                        'position' => $row['position'] ?? null,
                        'company_name' => $row['company_name'] ?? $row['company'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'work_phone' => $row['work_phone'] ?? $row['workphone'] ?? null,
                        'home_phone' => $row['home_phone'] ?? $row['homephone'] ?? null,
                        'address' => $row['address'] ?? null,
                        'additional_addresses' => $row['additional_addresses'] ?? $row['additionaladdresses'] ?? null,
                        'city' => $row['city'] ?? null,
                        'latitude' => $this->parseCoordinate($row['latitude'] ?? null),
                        'longitude' => $this->parseCoordinate($row['longitude'] ?? null),
                        'timezone' => $row['timezone'] ?? null,
                        'birthday' => $this->parseDate($row['birthday'] ?? null),
                        'notes' => $row['notes'] ?? null,
                        'tags' => !empty($row['tags']) ? json_encode($this->parseJsonOrArray($row['tags'])) : '[]',
                        'industries' => !empty($row['industries']) ? json_encode($this->parseJsonOrArray($row['industries'])) : '[]',
                        'socials' => !empty($row['socials']) ? json_encode($this->parseJsonOrObject($row['socials'])) : '{}',
                        'title' => $row['title'] ?? null,
                        'role' => $row['role'] ?? null,
                        'website_url' => $row['website_url'] ?? $row['websiteurl'] ?? null,
                    ];

                    // Skip rows without required fields
                    if (empty($mappedRow['first_name']) || empty($mappedRow['last_name'])) {
                        continue;
                    }

                    $batchData[] = $mappedRow;

                    // Insert batch when it reaches batch size
                    if (count($batchData) >= $batchSize) {
                        $this->insertBatchToTempTable($tempTableName, $batchData);
                        $batchData = [];
                    }
                }

                // Insert remaining data
                if (!empty($batchData)) {
                    $this->insertBatchToTempTable($tempTableName, $batchData);
                }

                fclose($handle);

                // Process the data in temporary table
                DB::statement("
                    UPDATE {$tempTableName} 
                    SET 
                        user_id = '{$userId}',
                        first_name = LOWER(TRIM(first_name)),
                        last_name = LOWER(TRIM(last_name)),
                        email = CASE 
                            WHEN email IS NOT NULL AND email != '' 
                            THEN LOWER(TRIM(email)) 
                            ELSE NULL 
                        END,
                        search_index = LOWER(TRIM(COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')))
                ");

                // Remove rows with missing required fields
                DB::statement("
                    DELETE FROM {$tempTableName} 
                    WHERE first_name IS NULL OR first_name = '' 
                       OR last_name IS NULL OR last_name = ''
                ");

                // Get counts before upsert
                $totalRows = DB::table($tempTableName)->count();
                $existingCount = DB::table('contacts')
                    ->where('user_id', $userId)
                    ->whereIn('email', function($query) use ($tempTableName) {
                        $query->select('email')
                              ->from($tempTableName)
                              ->whereNotNull('email')
                              ->where('email', '!=', '');
                    })
                    ->count();

                // Handle contacts with email (upsert with ON CONFLICT)
                DB::statement("
                    INSERT INTO contacts (
                        id, user_id, first_name, last_name, email, position, company_name,
                        phone, work_phone, home_phone, address, additional_addresses,
                        city, latitude, longitude, timezone, birthday, notes,
                        tags, industries, socials, title, role, website_url,
                        search_index, on_platform, has_sync, needs_sync, created_at, updated_at
                    )
                    SELECT 
                        id, user_id, first_name, last_name, email, position, company_name,
                        phone, work_phone, home_phone, address, additional_addresses,
                        city, latitude, longitude, timezone, birthday, notes,
                        tags, industries, socials, title, role, website_url,
                        search_index, on_platform, has_sync, needs_sync, created_at, updated_at
                    FROM {$tempTableName}
                    WHERE email IS NOT NULL AND email != ''
                    ON CONFLICT (user_id, email) 
                    DO UPDATE SET
                        first_name = EXCLUDED.first_name,
                        last_name = EXCLUDED.last_name,
                        position = EXCLUDED.position,
                        company_name = EXCLUDED.company_name,
                        phone = EXCLUDED.phone,
                        work_phone = EXCLUDED.work_phone,
                        home_phone = EXCLUDED.home_phone,
                        address = EXCLUDED.address,
                        additional_addresses = EXCLUDED.additional_addresses,
                        city = EXCLUDED.city,
                        latitude = EXCLUDED.latitude,
                        longitude = EXCLUDED.longitude,
                        timezone = EXCLUDED.timezone,
                        birthday = EXCLUDED.birthday,
                        notes = EXCLUDED.notes,
                        tags = EXCLUDED.tags,
                        industries = EXCLUDED.industries,
                        socials = EXCLUDED.socials,
                        title = EXCLUDED.title,
                        role = EXCLUDED.role,
                        website_url = EXCLUDED.website_url,
                        search_index = EXCLUDED.search_index,
                        updated_at = NOW()
                ");

                // Handle contacts without email (insert only, no conflict)
                DB::statement("
                    INSERT INTO contacts (
                        id, user_id, first_name, last_name, email, position, company_name,
                        phone, work_phone, home_phone, address, additional_addresses,
                        city, latitude, longitude, timezone, birthday, notes,
                        tags, industries, socials, title, role, website_url,
                        search_index, on_platform, has_sync, needs_sync, created_at, updated_at
                    )
                    SELECT 
                        id, user_id, first_name, last_name, email, position, company_name,
                        phone, work_phone, home_phone, address, additional_addresses,
                        city, latitude, longitude, timezone, birthday, notes,
                        tags, industries, socials, title, role, website_url,
                        search_index, on_platform, has_sync, needs_sync, created_at, updated_at
                    FROM {$tempTableName}
                    WHERE email IS NULL OR email = ''
                ");

                // Clean up temporary table
                DB::statement("DROP TABLE IF EXISTS {$tempTableName}");
            });

            $totalRecords = Contact::where('user_id', $userId)->count();
            $processingTime = round(microtime(true) - $startTime, 2);

            // Dispatch geocoding job for contacts that need coordinates
            $contactsNeedingGeocoding = Contact::where('user_id', $userId)
                ->whereNotNull('city')
                ->where(function($q) {
                    $q->whereNull('latitude')
                      ->orWhereNull('longitude')
                      ->orWhere('latitude', 0)
                      ->orWhere('longitude', 0);
                })
                ->count();

            if ($contactsNeedingGeocoding > 0) {
                GeocodeContactsJob::dispatch($userId);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'COPY import completed',
                'totalRecords' => $totalRecords,
                'data' => [
                    'summary' => [
                        'processingTime' => $processingTime . 's',
                        'method' => 'PostgreSQL COPY with ON CONFLICT upsert',
                        'contactsNeedingGeocoding' => $contactsNeedingGeocoding,
                        'geocodingJobDispatched' => $contactsNeedingGeocoding > 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            // Clean up temporary table on error
            DB::statement("DROP TABLE IF EXISTS {$tempTableName}");
            
            return response()->json([
                'statusCode' => 500,
                'message' => 'Import failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/contacts/geocode-pending",
     *     summary="Start geocoding for contacts without coordinates",
     *     description="Manually dispatch geocoding job for contacts that need coordinates",
     *     operationId="geocodePendingContacts",
     *     tags={"Contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Geocoding job dispatched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Geocoding job dispatched"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contactsNeedingGeocoding", type="integer", example=150)
     *             )
     *         )
     *     )
     * )
     */
    public function geocodePendingContacts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Count contacts that need geocoding
        $contactsNeedingGeocoding = Contact::where('user_id', $userId)
            ->whereNotNull('city')
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            })
            ->count();

        if ($contactsNeedingGeocoding === 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'No contacts need geocoding',
                'data' => [
                    'contactsNeedingGeocoding' => 0
                ]
            ]);
        }

        // Dispatch geocoding job
        GeocodeContactsJob::dispatch($userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Geocoding job dispatched',
            'data' => [
                'contactsNeedingGeocoding' => $contactsNeedingGeocoding
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/contacts/geocoding-status",
     *     summary="Get geocoding status for user contacts",
     *     description="Check how many contacts need geocoding and their status",
     *     operationId="getGeocodingStatus",
     *     tags={"Contacts"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Geocoding status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="totalContacts", type="integer", example=1000),
     *                 @OA\Property(property="contactsWithCoordinates", type="integer", example=850),
     *                 @OA\Property(property="contactsNeedingGeocoding", type="integer", example=150),
     *                 @OA\Property(property="geocodingProgress", type="string", example="85%")
     *             )
     *         )
     *     )
     * )
     */
    public function getGeocodingStatus(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $totalContacts = Contact::where('user_id', $userId)->count();
        
        $contactsWithCoordinates = Contact::where('user_id', $userId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->count();

        $contactsNeedingGeocoding = $totalContacts - $contactsWithCoordinates;
        $geocodingProgress = $totalContacts > 0 ? round(($contactsWithCoordinates / $totalContacts) * 100, 1) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'totalContacts' => $totalContacts,
                'contactsWithCoordinates' => $contactsWithCoordinates,
                'contactsNeedingGeocoding' => $contactsNeedingGeocoding,
                'geocodingProgress' => $geocodingProgress . '%'
            ]
        ]);
    }

    /**
     * Insert batch data into temporary table
     */
    private function insertBatchToTempTable($tempTableName, $batchData): void
    {
        if (empty($batchData)) {
            return;
        }

        // Prepare data for bulk insert
        $values = [];
        $placeholders = [];

        foreach ($batchData as $row) {
            $placeholders[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
            $values = array_merge($values, array_values($row));
        }

        $columns = implode(',', array_keys($batchData[0]));
        $placeholderString = implode(',', $placeholders);

        $sql = "INSERT INTO {$tempTableName} ({$columns}) VALUES {$placeholderString}";
        
        DB::statement($sql, $values);
    }

    /**
     * Parse JSON string or comma/semicolon separated string into array
     */
    private function parseJsonOrArray($value): array
    {
        if (empty($value) || $value === '' || $value === 'null') {
            return [];
        }

        // Try to decode as JSON first
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // If not JSON, split by comma or semicolon
        $parts = preg_split('/[;,]/', $value);
        return array_values(array_filter(array_map('trim', $parts), function($part) {
            return !empty($part) && $part !== 'null';
        }));
    }

    /**
     * Parse JSON string or comma/semicolon separated string into object
     */
    private function parseJsonOrObject($value): array
    {
        if (empty($value) || $value === '' || $value === 'null') {
            return [];
        }

        // Try to decode as JSON first
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // If not JSON, try to parse as key:value pairs
        $parts = preg_split('/[;,]/', $value);
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === 'null') continue;
            
            if (strpos($part, ':') !== false) {
                list($key, $val) = explode(':', $part, 2);
                $result[trim($key)] = trim($val);
            } else {
                // If no colon, treat as key with empty value
                $result[$part] = '';
            }
        }
        
        return $result;
    }

    /**
     * Parse coordinate value (latitude/longitude)
     */
    private function parseCoordinate($value)
    {
        if (empty($value) || $value === '' || $value === '?' || $value === 'null') {
            return null;
        }

        $floatValue = (float) $value;
        return $floatValue != 0 ? $floatValue : null;
    }

    /**
     * Parse date value
     */
    private function parseDate($value)
    {
        if (empty($value) || $value === '' || $value === 'null') {
            return null;
        }

        // Try to parse the date
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Format contact for API response
     */
    private function formatContact($contact): array
    {
        // Ensure socials is always an object, not an array
        // Frontend expects an object for Object.entries()
        $socials = $contact->socials;
        
        if (is_null($socials)) {
            $socials = new \stdClass(); // Empty object for JSON
        } elseif (is_array($socials)) {
            // Filter out null values
            $socials = array_filter($socials, function($value) {
                return !is_null($value) && $value !== '';
            });
            
            if (empty($socials)) {
                $socials = new \stdClass(); // Empty object for JSON
            } else {
                // Convert associative array to object, maintaining key-value pairs
                $socials = (object) $socials;
            }
        }

        return [
            'id' => $contact->id,
            'firstName' => ucwords($contact->first_name),
            'lastName' => ucwords($contact->last_name),
            'name' => ucwords($contact->first_name . ' ' . $contact->last_name),
            'email' => $contact->email,
            'position' => $contact->position,
            'company' => $contact->company_name,
            'phone' => $contact->phone,
            'workPhone' => $contact->work_phone,
            'homePhone' => $contact->home_phone,
            'address' => $contact->address,
            'additionalAddresses' => $contact->additional_addresses,
            'city' => $contact->city,
            'latitude' => $contact->latitude,
            'longitude' => $contact->longitude,
            'timezone' => $contact->timezone,
            'title' => $contact->title,
            'role' => $contact->role,
            'websiteUrl' => $contact->website_url,
            'birthday' => $contact->birthday,
            'notes' => $contact->notes,
            'tags' => $contact->tags ?? [],
            'industries' => $contact->industries ?? [],
            'socials' => $socials,
            'onPlatform' => $contact->on_platform,
            'hasSync' => $contact->has_sync,
            'needsSync' => $contact->needs_sync,
            'created_at' => $contact->created_at->toISOString(),
            'updated_at' => $contact->updated_at->toISOString(),
        ];
    }
}
