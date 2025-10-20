<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitiesSampleSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            // name, state, country, lat, lng, timezone
            ['Seattle', null, null, 47.6062, -122.3321, 'America/Los_Angeles'],
            ['New York', null, null, 40.7128, -74.0060, 'America/New_York'],
            ['Chicago', null, null, 41.8781, -87.6298, 'America/Chicago'],
            ['San Francisco', null, null, 37.7749, -122.4194, 'America/Los_Angeles'],
            ['Boston', null, null, 42.3601, -71.0589, 'America/New_York'],
            ['Miami', null, null, 25.7617, -80.1918, 'America/New_York'],
            ['Austin', null, null, 30.2672, -97.7431, 'America/Chicago'],
            ['Denver', null, null, 39.7392, -104.9903, 'America/Denver'],
            ['Phoenix', null, null, 33.4484, -112.0740, 'America/Phoenix'],
            ['Portland', null, null, 45.5152, -122.6784, 'America/Los_Angeles'],
            ['San Jose', null, null, 37.3382, -121.8863, 'America/Los_Angeles'],
            ['Dallas', null, null, 32.7767, -96.7970, 'America/Chicago'],
            ['Atlanta', null, null, 33.7490, -84.3880, 'America/New_York'],
            ['Los Angeles', null, null, 34.0522, -118.2437, 'America/Los_Angeles'],
            ['Houston', null, null, 29.7604, -95.3698, 'America/Chicago'],
            ['Philadelphia', null, null, 39.9526, -75.1652, 'America/New_York'],
            ['San Diego', null, null, 32.7157, -117.1611, 'America/Los_Angeles'],
            ['San Antonio', null, null, 29.4241, -98.4936, 'America/Chicago'],
            ['Jacksonville', null, null, 30.3322, -81.6557, 'America/New_York'],
            ['Columbus', null, null, 39.9612, -82.9988, 'America/New_York'],
        ];

        foreach ($cities as [$name, $state, $country, $lat, $lng, $tz]) {
            City::updateOrCreate(
                ['name' => $name, 'state' => $state, 'country' => $country],
                ['latitude' => $lat, 'longitude' => $lng, 'timezone' => $tz]
            );
        }
    }
}


