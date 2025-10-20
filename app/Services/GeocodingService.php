<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    public function hasValidCoordinates($lat, $lng): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }
        return is_numeric($lat) && is_numeric($lng);
    }

    /**
     * Resolve coordinates from local cities cache only.
     * Accepts city and optional state/country for better precision.
     * Maintains BC with previous callers by allowing single argument.
     */
    public function geocode(string $cityName, ?string $state = null, ?string $country = null): ?array
    {
        $name = trim($cityName);
        if ($name === '') {
            return null;
        }

        $query = City::query();
        $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        if ($state !== null && $state !== '') {
            $query->where(function ($q) use ($state) {
                $q->whereRaw('LOWER(state) = ?', [strtolower($state)])
                  ->orWhereNull('state');
            });
        }
        if ($country !== null && $country !== '') {
            $query->where(function ($q) use ($country) {
                $q->whereRaw('LOWER(country) = ?', [strtolower($country)])
                  ->orWhereNull('country');
            });
        }

        $city = $query->first();
        if ($city && $this->hasValidCoordinates($city->latitude, $city->longitude)) {
            return [
                'latitude' => (float) $city->latitude,
                'longitude' => (float) $city->longitude,
                'timezone' => $city->timezone,
            ];
        }

        // Fallback: Google Maps Geocoding
        $apiKey = config('services.google.maps_api_key') ?? env('GOOGLE_MAPS_API_KEY');
        if (!$apiKey) {
            return null;
        }

        $addressParts = array_filter([$name, $state, $country]);
        $params = [
            'address' => implode(', ', $addressParts),
            'key' => $apiKey,
        ];

        $response = Http::timeout(20)->get('https://maps.googleapis.com/maps/api/geocode/json', $params);
        if (!$response->ok()) {
            return null;
        }
        $body = $response->json();
        if (($body['status'] ?? '') !== 'OK' || empty($body['results'][0]['geometry']['location'])) {
            return null;
        }
        $loc = $body['results'][0]['geometry']['location'];
        $lat = $loc['lat'] ?? null;
        $lng = $loc['lng'] ?? null;
        if (!$this->hasValidCoordinates($lat, $lng)) {
            return null;
        }

        // Cache in cities table
        $this->upsertCity($name, $state, $country, $lat, $lng, null);

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'timezone' => null,
        ];
    }

    /**
     * Upsert a city record when we already have coordinates (e.g., from import CSV).
     */
    public function upsertCity(string $name, ?string $state, ?string $country, $latitude, $longitude, ?string $timezone = null): void
    {
        $name = trim($name);
        if ($name === '' || !$this->hasValidCoordinates($latitude, $longitude)) {
            return;
        }

        $existing = City::where('name', $name)
            ->where('state', $state)
            ->where('country', $country)
            ->first();

        if ($existing) {
            $existing->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => $timezone,
            ]);
            return;
        }

        City::create([
            'name' => $name,
            'state' => $state,
            'country' => $country,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => $timezone,
        ]);
    }
}
