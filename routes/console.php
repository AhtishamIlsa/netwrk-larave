<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\City;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate a large sample CSV of contacts without lat/long, using cities from DB
Artisan::command('contacts:generate-sample-csv {--rows=3000} {--output=storage/sample-contacts-3k.csv}', function () {
    $rows = (int) $this->option('rows');
    $output = (string) $this->option('output');
    if ($rows < 1) { $this->error('rows must be >= 1'); return 1; }

    $cities = City::query()->select('name')->pluck('name')->filter()->values()->all();
    if (empty($cities)) { $this->error('No cities found in DB. Seed CitiesSampleSeeder first.'); return 1; }

    $header = 'firstName,lastName,email,company,position,phone,address,city,latitude,longitude,timezone,birthday,notes,tags,industries,websiteUrl,workPhone,homePhone,additionalAddresses,socials';
    $lines = [$header];

    $firstNames = ['Alex','Brooke','Chris','Drew','Evan','Faith','Gabe','Harper','Ian','Jade','Kai','Liam','Maya','Noah','Olivia','Parker','Quinn','Riley','Sawyer','Taylor','Uma','Vic','Wren','Xavier','Yara','Zane','Aaron','Bella','Cody','Dina','Eli','Faye','Gus','Hope','Iris','Joel','Kira','Leo','Mila','Nico','Owen','Pia','Qiana','Rex','Sia','Troy','Una','Vera','Walt','Xena','Yves','Zoe'];
    $lastNames  = ['Johnson','Williams','Brown','Smith','Taylor','Moore','Martin','Lee','Clark','Hall','Allen','Young','King','Wright','Scott','Green','Baker','Adams','Nelson','Carter','Rivera','Cooper','Richardson','Cox','Howard','Ward','Brooks','Watson','Nguyen','Ross','Peterson','Gray','Ramirez','James','Reed','Morgan','Kim','Butler','Barnes','Long','Foster','Gonzalez','Bryant','Alexander','Hamilton','Griffin','Diaz','Hayes','Hughes','Myers','Jordan','Montgomery'];

    for ($i = 1; $i <= $rows; $i++) {
        $fn = $firstNames[($i - 1) % count($firstNames)];
        $ln = $lastNames[($i - 1) % count($lastNames)];
        $email = strtolower($fn . '.' . $ln) . sprintf('+%04d', $i) . '@example.com';
        $company = ['Acme Corp','Globex','Initech','Stark Industries','Wonka Inc','Wayne Enterprises'][($i - 1) % 6];
        $position = ['Engineer','Manager','Analyst','Director','Architect','PM'][($i - 1) % 6];
        $phone = '+1-555-' . str_pad((string)(1000 + $i), 4, '0');
        $city = $cities[($i - 1) % count($cities)];

        // CSV fields: leave lat/long blank; other optional fields blank
        $row = [
            $fn,
            $ln,
            $email,
            $company,
            $position,
            $phone,
            '',
            $city,
            '',
            '',
            '',
            '',
            'Generated row ' . $i,
            '',
            '',
            '',
            '',
            '',
            '',
        ];
        // Escape and join
        $escaped = array_map(function($v){
            $v = (string) $v;
            if (str_contains($v, ',') || str_contains($v, '"')) {
                $v = '"' . str_replace('"', '""', $v) . '"';
            }
            return $v;
        }, $row);
        $lines[] = implode(',', $escaped);
    }

    // Ensure directory exists
    $dir = dirname($output);
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    file_put_contents($output, implode("\n", $lines) . "\n");
    $this->info("Generated {$rows} rows at {$output}");
    return 0;
})->purpose('Generate a large sample contacts CSV without lat/long, only city');
