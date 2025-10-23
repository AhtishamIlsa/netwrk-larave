<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Services\GeocodingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeocodeContactsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $userId;
    protected $contactIds;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $contactIds = null)
    {
        $this->userId = $userId;
        $this->contactIds = $contactIds;
    }

    /**
     * Execute the job.
     */
    public function handle(GeocodingService $geocodingService): void
    {
        Log::info("Starting bulk geocoding job for user {$this->userId}");

        // Get contacts that need geocoding
        $query = Contact::where('user_id', $this->userId)
            ->whereNotNull('city')
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            });

        // If specific contact IDs provided, filter by them
        if ($this->contactIds) {
            $query->whereIn('id', $this->contactIds);
        }

        $contacts = $query->get();
        
        if ($contacts->isEmpty()) {
            Log::info("No contacts need geocoding for user {$this->userId}");
            return;
        }

        Log::info("Found {$contacts->count()} contacts to geocode for user {$this->userId}");

        // Step 1: Bulk lookup from cities table
        $this->bulkUpdateFromCitiesTable($contacts);

        // Step 2: Get remaining contacts that still need geocoding
        $remainingContacts = $contacts->filter(function($contact) use ($geocodingService) {
            return !$geocodingService->hasValidCoordinates($contact->latitude, $contact->longitude);
        });

        if ($remainingContacts->isEmpty()) {
            Log::info("All contacts geocoded from cities table for user {$this->userId}");
            return;
        }

        Log::info("Found {$remainingContacts->count()} contacts still need geocoding from API");

        // Step 3: Bulk geocode remaining cities
        $this->bulkGeocodeFromAPI($remainingContacts, $geocodingService);
    }

    /**
     * Bulk update contacts from cities table
     */
    private function bulkUpdateFromCitiesTable($contacts): void
    {
        // Get unique cities that need geocoding
        $uniqueCities = $contacts->map(function($contact) {
            return [
                'city' => $contact->city,
                'state' => $contact->state ?? null,
                'country' => $contact->country ?? null,
            ];
        })->unique(function($item) {
            return $item['city'] . '|' . ($item['state'] ?? '') . '|' . ($item['country'] ?? '');
        });

        Log::info("Looking up {$uniqueCities->count()} unique cities in cities table");

        $citiesFound = 0;
        $cityCoordinates = [];

        foreach ($uniqueCities as $cityData) {
            $city = \App\Models\City::where('name', $cityData['city'])
                ->when($cityData['state'], function($query, $state) {
                    return $query->where(function($q) use ($state) {
                        $q->where('state', $state)->orWhereNull('state');
                    });
                })
                ->when($cityData['country'], function($query, $country) {
                    return $query->where(function($q) use ($country) {
                        $q->where('country', $country)->orWhereNull('country');
                    });
                })
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', 0)
                ->where('longitude', '!=', 0)
                ->first();

            if ($city) {
                $key = $cityData['city'] . '|' . ($cityData['state'] ?? '') . '|' . ($cityData['country'] ?? '');
                $cityCoordinates[$key] = [
                    'latitude' => (float) $city->latitude,
                    'longitude' => (float) $city->longitude,
                    'timezone' => $city->timezone,
                ];
                $citiesFound++;
            }
        }

        Log::info("Found {$citiesFound} cities in cities table");

        // Bulk update contacts with coordinates from cities table
        $updated = 0;
        $updates = [];

        foreach ($contacts as $contact) {
            $key = $contact->city . '|' . ($contact->state ?? '') . '|' . ($contact->country ?? '');
            if (isset($cityCoordinates[$key])) {
                $updates[] = [
                    'id' => $contact->id,
                    'latitude' => $cityCoordinates[$key]['latitude'],
                    'longitude' => $cityCoordinates[$key]['longitude'],
                    'timezone' => $cityCoordinates[$key]['timezone'] ?? $contact->timezone,
                ];
            }
        }

        // Bulk update using single query
        if (!empty($updates)) {
            DB::transaction(function() use ($updates) {
                foreach ($updates as $update) {
                    DB::table('contacts')
                        ->where('id', $update['id'])
                        ->update([
                            'latitude' => $update['latitude'],
                            'longitude' => $update['longitude'],
                            'timezone' => $update['timezone'],
                            'updated_at' => now(),
                        ]);
                }
            });
            $updated = count($updates);
        }

        Log::info("Updated {$updated} contacts from cities table");
    }

    /**
     * Bulk geocode remaining contacts from API
     */
    private function bulkGeocodeFromAPI($contacts, GeocodingService $geocodingService): void
    {
        // Get unique cities that still need geocoding
        $uniqueCities = $contacts->map(function($contact) {
            return [
                'city' => $contact->city,
                'state' => $contact->state ?? null,
                'country' => $contact->country ?? null,
            ];
        })->unique(function($item) {
            return $item['city'] . '|' . ($item['state'] ?? '') . '|' . ($item['country'] ?? '');
        });

        Log::info("Geocoding {$uniqueCities->count()} unique cities from API");

        $geocoded = 0;
        $failed = 0;
        $cityCoordinates = [];

        foreach ($uniqueCities as $cityData) {
            try {
                $geo = $geocodingService->geocode(
                    $cityData['city'],
                    $cityData['state'],
                    $cityData['country']
                );

                if ($geo && isset($geo['latitude'], $geo['longitude'])) {
                    $key = $cityData['city'] . '|' . ($cityData['state'] ?? '') . '|' . ($cityData['country'] ?? '');
                    $cityCoordinates[$key] = [
                        'latitude' => (float) $geo['latitude'],
                        'longitude' => (float) $geo['longitude'],
                        'timezone' => $geo['timezone'] ?? null,
                    ];
                    $geocoded++;
                } else {
                    $failed++;
                    Log::warning("Failed to geocode city: {$cityData['city']}");
                }

                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 seconds

            } catch (\Exception $e) {
                $failed++;
                Log::error("Error geocoding city {$cityData['city']}: " . $e->getMessage());
            }
        }

        // Bulk update contacts with API geocoded coordinates
        $updated = 0;
        $updates = [];

        foreach ($contacts as $contact) {
            $key = $contact->city . '|' . ($contact->state ?? '') . '|' . ($contact->country ?? '');
            if (isset($cityCoordinates[$key])) {
                $updates[] = [
                    'id' => $contact->id,
                    'latitude' => $cityCoordinates[$key]['latitude'],
                    'longitude' => $cityCoordinates[$key]['longitude'],
                    'timezone' => $cityCoordinates[$key]['timezone'] ?? $contact->timezone,
                ];
            }
        }

        // Bulk update using single query
        if (!empty($updates)) {
            DB::transaction(function() use ($updates) {
                foreach ($updates as $update) {
                    DB::table('contacts')
                        ->where('id', $update['id'])
                        ->update([
                            'latitude' => $update['latitude'],
                            'longitude' => $update['longitude'],
                            'timezone' => $update['timezone'],
                            'updated_at' => now(),
                        ]);
                }
            });
            $updated = count($updates);
        }

        Log::info("Geocoding completed: {$geocoded} cities geocoded, {$failed} failed, {$updated} contacts updated");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Geocoding job failed for user {$this->userId}: " . $exception->getMessage());
    }
}
