<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "SCHEDULE rounds:" . PHP_EOL;

foreach (DB::table('schedules')->select('type', 'round')->distinct()->get() as $r) {
    echo '  type=' . $r->type . ' round=' . var_export($r->round, true) . PHP_EOL;
}

echo "MATCHUP rounds:" . PHP_EOL;

foreach (DB::table('matchups')->select('round')->distinct()->get() as $r) {
    echo '  round=' . var_export($r->round, true) . PHP_EOL;
}

echo "BRACKET slots round values:" . PHP_EOL;

foreach (DB::table('bracket_slots')->select('round')->distinct()->get() as $r) {
    echo '  round=' . var_export($r->round, true) . PHP_EOL;
}
