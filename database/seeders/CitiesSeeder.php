<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CitiesSeeder extends Seeder
{
    /**
     * Seed the application's database with cities.
     * Usage: php artisan db:seed --class=CitiesSeeder --url="https://..." [--bearer=TOKEN]
     */
    public function run(): void
    {
        $neptuneHost = (string) (getenv('NEPTUNE_READ_ENDPOINT') ?: 'netwrk-dev-staging-neptune-db-00.cluster-ro-cnmsm6agmsyw.us-west-2.neptune.amazonaws.com');
        $neptunePort = (string) (getenv('NEPTUNE_PORT') ?: '8182');
        // dd('neptuneHost: ' . $neptuneHost . ' neptunePort: ' . $neptunePort);

        // Optional fallbacks via env
        $url = (string) (getenv('CITIES_SEED_URL') ?: '');
        $bearer = (string) (getenv('CITIES_SEED_BEARER') ?: '');
        $file = (string) (getenv('CITIES_SEED_FILE') ?: '');
        $list = [];
        // Try Neptune HTTP Gremlin first (requires VPC access and IAM disabled)
        if ($neptuneHost !== '') {
            $endpoint = 'https://' . $neptuneHost . ':' . $neptunePort . '/gremlin';
            try {
                $gremlin = "g.V().hasLabel('City').project('name','state','country','latitude','longitude','timezone').by(values('name')).by(values('state')).by(values('country')).by(values('latitude')).by(values('longitude')).by(values('timezone'))";
                $resp = Http::timeout(300)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->post($endpoint, [ 'gremlin' => $gremlin ]);
                // dd($resp->json());
                if ($resp->ok()) {
                    $payload = $resp->json();
                    if (isset($payload['result']['data']) && is_array($payload['result']['data'])) {
                        $list = $payload['result']['data'];
                    } elseif (isset($payload['data']) && is_array($payload['data'])) {
                        $list = $payload['data'];
                    }
                } else {
                    $this->command?->warn('Neptune HTTP call failed: HTTP ' . $resp->status());
                }
            } catch (\Throwable $e) {
                $this->command?->warn('Neptune fetch error: ' . $e->getMessage());
            }
        }

        if (empty($list) && $url !== '') {
            $http = Http::timeout(300);
            if ($bearer !== '') $http = $http->withToken($bearer);
            $resp = $http->get($url);
            if ($resp->ok()) {
                $payload = $resp->json();
                if (isset($payload['data']) && is_array($payload['data'])) {
                    $list = $payload['data'];
                } elseif (is_array($payload)) {
                    $list = $payload;
                }
            }
        } elseif (empty($list) && $file !== '' && file_exists($file)) {
            $payload = json_decode(file_get_contents($file), true);
            if (is_array($payload)) {
                $list = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
            }
        }

        if (empty($list)) {
            $this->command?->warn('CitiesSeeder: no input provided. Set CITIES_SEED_URL or CITIES_SEED_FILE.');
            return;
        }

        DB::transaction(function () use ($list) {
            foreach ($list as $c) {
                $name = $c['name'] ?? $c['city'] ?? null;
                if (!$name) continue;
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
                } else {
                    City::create([
                        'name' => $name,
                        'state' => $state,
                        'country' => $country,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'timezone' => $tz,
                    ]);
                }
            }
        });
    }
}


