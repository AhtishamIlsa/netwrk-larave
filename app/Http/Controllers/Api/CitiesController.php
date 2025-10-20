<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CitiesController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/cities/import",
     *     summary="Import cities (cache for geocoding)",
     *     tags={"cities"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", example="https://api.staging.netwrk.vip/api/cities"),
     *             @OA\Property(property="bearer", type="string", example="<token>")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imported",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Cities imported"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="created", type="integer", example=100),
     *                 @OA\Property(property="updated", type="integer", example=50),
     *                 @OA\Property(property="skipped", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'bearer' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => [ 'errors' => $validator->errors() ]
            ], 422);
        }

        $url = $request->input('url');
        $bearer = $request->input('bearer');

        $http = Http::timeout(120);
        if ($bearer) {
            $http = $http->withToken($bearer);
        }

        $resp = $http->get($url);
        if (!$resp->ok()) {
            return response()->json([
                'statusCode' => $resp->status(),
                'message' => 'Failed to fetch cities',
                'data' => null
            ], $resp->status());
        }

        $payload = $resp->json();
        $list = [];
        // Accept { data: [...] } or plain array
        if (isset($payload['data']) && is_array($payload['data'])) {
            $list = $payload['data'];
        } elseif (is_array($payload)) {
            $list = $payload;
        }

        $created = 0; $updated = 0; $skipped = 0;
        DB::beginTransaction();
        try {
            foreach ($list as $c) {
                $name = $c['name'] ?? $c['city'] ?? null;
                if (!$name) { $skipped++; continue; }
                $state = $c['state'] ?? ($c['state_code'] ?? null);
                $country = $c['country'] ?? ($c['country_code'] ?? null);
                $lat = $c['latitude'] ?? $c['lat'] ?? null;
                $lng = $c['longitude'] ?? $c['lng'] ?? null;
                $tz = $c['timezone'] ?? null;

                $existing = City::where('name', $name)
                    ->where('state', $state)
                    ->where('country', $country)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'timezone' => $tz,
                    ]);
                    $updated++;
                } else {
                    City::create([
                        'name' => $name,
                        'state' => $state,
                        'country' => $country,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'timezone' => $tz,
                    ]);
                    $created++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'statusCode' => 500,
                'message' => 'Import failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Cities imported',
            'data' => [ 'created' => $created, 'updated' => $updated, 'skipped' => $skipped ]
        ]);
    }
}


