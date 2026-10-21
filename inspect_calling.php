<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Schedule;

echo "TODAY: " . now()->toDateString() . PHP_EOL . PHP_EOL;

$schedules = Schedule::with('match', 'performances', 'category', 'arena')->orderBy('id')->get();

foreach ($schedules as $s) {
    printf(
        "#%d | %-8s | date=%-12s | status=%-12s | type=%-8s | cat=%-22s | arena=%-10s | match=%-3s | perf=%d | athletes=%d%s",
        $s->id,
        $s->match_no,
        $s->match_date ? $s->match_date->toDateString() : 'null',
        $s->status,
        $s->type,
        $s->category?->name ?? '-',
        $s->arena?->name ?? '-',
        $s->match ? 'yes' : 'NO',
        $s->performances->count(),
        $s->athletes()->count(),
        PHP_EOL
    );
}

echo PHP_EOL . "MATCHUPS:" . PHP_EOL;

foreach (App\Models\Matchup::with('athleteA', 'athleteB')->get() as $m) {
    printf(
        "#%d | schedule=%-4s | %-8s | status=%-10s | A=%-20s | B=%-20s%s",
        $m->id,
        $m->schedule_id ?? '-',
        $m->match_no ?? '-',
        $m->status,
        $m->athleteA?->name ?? '-',
        $m->athleteB?->name ?? '-',
        PHP_EOL
    );
}

echo PHP_EOL . "PERFORMANCES:" . PHP_EOL;

foreach (App\Models\Performance::with('athlete')->get() as $p) {
    printf(
        "#%d | schedule=%-4s | order=%-3d | status=%-12s | %s%s",
        $p->id,
        $p->schedule_id ?? '-',
        $p->order_no,
        $p->status,
        $p->athlete?->name ?? '-',
        PHP_EOL
    );
}

echo PHP_EOL . "ATHLETES: " . App\Models\Athlete::count() . PHP_EOL;
