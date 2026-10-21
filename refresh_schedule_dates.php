<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Schedule;

$today = now()->toDateString();

$updated = Schedule::whereDate('match_date', '<', $today)->update([
    'match_date' => $today,
]);

echo "Updated schedules: {$updated}" . PHP_EOL;
echo 'Today: ' . $today . PHP_EOL;

foreach (Schedule::with('match', 'performances')->get() as $schedule) {
    printf(
        "#%d | %-6s | %-12s | %-9s | athletes=%d%s",
        $schedule->id,
        $schedule->match_no,
        $schedule->match_date->toDateString(),
        $schedule->type,
        $schedule->athletes()->count(),
        PHP_EOL
    );
}
