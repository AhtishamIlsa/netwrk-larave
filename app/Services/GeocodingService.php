<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = Config::get('services.google.maps_api_key');
    }

    /**
     * Geocode an address/city to get latitude and longitude
     *
     * @param string $address
     * @return array|null Returns ['latitude' => float, 'longitude' => float] or null
     */
    public function geocode(string $address): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Google Maps API key is not configured');
            return null;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $this->apiKey,
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                
                return [
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                ];
            }

            Log::info("Geocoding failed for address: {$address}", ['response' => $data]);
            return null;
        } catch (\Exception $e) {
            Log::error('Geocoding error: ' . $e->getMessage(), [
                'address' => $address,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Check if coordinates are valid
     *
     * @param float|null $latitude
     * @param float|null $longitude
     * @return bool
     */
    public function hasValidCoordinates(?float $latitude, ?float $longitude): bool
    {
        return is_numeric($latitude) && 
               is_numeric($longitude) && 
               $latitude >= -90 && 
               $latitude <= 90 && 
               $longitude >= -180 && 
               $longitude <= 180;
    }
}
