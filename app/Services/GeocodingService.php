<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Geocode an address/city using Google Maps Geocoding API
     *
     * @param string $address
     * @return array|null Returns ['latitude' => float, 'longitude' => float, 'formatted_address' => string] or null
     */
    public function geocode(string $address): ?array
    {
        $apiKey = config('services.google.maps_api_key');

        if (empty($apiKey)) {
            Log::warning('Google Maps API key not configured');
            return null;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey
            ]);

            $data = $response->json();

            // Check if we got valid results
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $result = $data['results'][0];
                
                return [
                    'latitude' => $result['geometry']['location']['lat'],
                    'longitude' => $result['geometry']['location']['lng'],
                    'formatted_address' => $result['formatted_address'] ?? null,
                ];
            }

            // Log if no results found
            if ($data['status'] === 'ZERO_RESULTS') {
                Log::info("No geocoding results found for address: {$address}");
            } else {
                Log::warning("Geocoding API returned status: {$data['status']} for address: {$address}");
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Geocoding API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Reverse geocode coordinates to get address/city
     *
     * @param float $latitude
     * @param float $longitude
     * @return array|null
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        $apiKey = config('services.google.maps_api_key');

        if (empty($apiKey)) {
            Log::warning('Google Maps API key not configured');
            return null;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $apiKey
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $result = $data['results'][0];
                
                // Extract city from address components
                $city = null;
                foreach ($result['address_components'] as $component) {
                    if (in_array('locality', $component['types'])) {
                        $city = $component['long_name'];
                        break;
                    }
                }

                return [
                    'city' => $city,
                    'formatted_address' => $result['formatted_address'] ?? null,
                    'address_components' => $result['address_components'] ?? [],
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Reverse geocoding API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if coordinates are valid
     *
     * @param mixed $latitude
     * @param mixed $longitude
     * @return bool
     */
    public function hasValidCoordinates($latitude, $longitude): bool
    {
        return !is_null($latitude) 
            && !is_null($longitude) 
            && is_numeric($latitude) 
            && is_numeric($longitude)
            && $latitude >= -90 
            && $latitude <= 90
            && $longitude >= -180 
            && $longitude <= 180;
    }
}

