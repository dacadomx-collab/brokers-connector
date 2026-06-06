<?php
chdir("c:/xampp/htdocs/brokersconnect_dev/brokers_new");
define('LARAVEL_START', microtime(true));
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$companiesWithGeo = DB::table('properties')
    ->whereNull('deleted_at')->where('published', 1)->where('price', '>', 0)
    ->whereNotNull('zipcode')->whereNotNull('lat')->where('lat', '<>', '')
    ->select('company_id', DB::raw('COUNT(*) as total'), DB::raw('COUNT(DISTINCT zipcode) as zonas'))
    ->groupBy('company_id')->orderByDesc('total')->get();

echo "=== Companies con geo ===\n";
foreach ($companiesWithGeo as $c) {
    echo "company_id={$c->company_id} props={$c->total} zonas={$c->zonas}\n";
}

if ($companiesWithGeo->isNotEmpty()) {
    $cid = $companiesWithGeo->first()->company_id;
    echo "\n=== computeZones company_id={$cid} ===\n";
    $zones = DB::table('properties')
        ->where('company_id', $cid)->whereNull('deleted_at')->where('published', 1)->where('price', '>', 0)->whereNotNull('zipcode')
        ->select('zipcode',
            DB::raw("AVG(CASE WHEN lat<>'' AND lat IS NOT NULL THEN CAST(lat AS DECIMAL(10,7)) ELSE NULL END) as clat"),
            DB::raw("AVG(CASE WHEN lng<>'' AND lng IS NOT NULL THEN CAST(lng AS DECIMAL(10,7)) ELSE NULL END) as clng"),
            DB::raw('COUNT(id) as n'),
            DB::raw('AVG(price) as avg_p'),
            DB::raw('AVG(CASE WHEN built_area>0 THEN price/built_area WHEN total_area>0 THEN price/total_area ELSE NULL END) as pm2')
        )
        ->groupBy('zipcode')->havingRaw('COUNT(id)>=1')->get();
    foreach ($zones as $z) {
        echo "zip={$z->zipcode} n={$z->n} avg=" . number_format($z->avg_p) . " pm2=" . ($z->pm2 ? number_format($z->pm2,0) : 'NULL') . " lat={$z->clat}\n";
    }
}

echo "\n=== ai_zone_heatmaps rows: " . DB::table('ai_zone_heatmaps')->count() . " ===\n";
echo "=== ai_prompts ===\n";
foreach (DB::table('ai_prompts')->select('id','slug','is_active')->get() as $p) {
    echo "id={$p->id} slug={$p->slug} active={$p->is_active}\n";
}
