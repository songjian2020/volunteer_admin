<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$loc = Illuminate\Support\Facades\DB::table('vol_activity')->where('id', 1)->value('location');
echo "activity location: {$loc}\n";

$r = Modules\Volunteer\Services\GeocodeService::geocode((string) $loc);
echo "geocode loc: ";
var_export($r);
echo "\n";

$r2 = Modules\Volunteer\Services\GeocodeService::geocode('重庆市沙坪坝区');
echo "geocode city: ";
var_export($r2);
echo "\n";

$ctx = stream_context_create([
    'http' => ['timeout' => 8, 'header' => "User-Agent: VolunteerAdmin/1.0\r\n"],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
]);
$raw = @file_get_contents('https://nominatim.openstreetmap.org/search?q=Chongqing&format=json&limit=1', false, $ctx);
echo "nominatim raw: " . substr((string) $raw, 0, 160) . "\n";
