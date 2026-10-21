<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Performance;
use App\Models\Score;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoreController extends Controller
{
    public function edit(Performance $performance)
    {
        $performance->load([
            'schedule.category',
            'schedule.arena',
            'athlete.contingent',
            'scores',
        ]);

        $judgeScores = [];

        foreach ([1, 2, 3] as $judgeNo) {
            $score = $performance->scores->first(fn (Score $item) => (int) $item->judge_no === $judgeNo);
            $judgeScores[$judgeNo] = $score ? (float) $score->score : null;
        }

        return view('admin.score.edit', [
            'performance' => $performance,
            'judgeScores' => $judgeScores,
            'finalScore' => $performance->final_score !== null
                ? (float) $performance->final_score
                : null,
        ]);
    }

    public function store(Request $request, Performance $performance): RedirectResponse
    {
        $data = $request->validate(
            [
                'final_score' => ['required', 'numeric', 'min:0', 'max:99999'],
            ],
            [
                'final_score.required' => 'Nilai akhir wajib diisi.',
                'final_score.numeric' => 'Nilai akhir harus berupa angka.',
                'final_score.min' => 'Nilai akhir minimal 0.',
                'final_score.max' => 'Nilai akhir maksimal 99999.',
            ]
        );

        $finalScore = round((float) $data['final_score'], 2);

        DB::transaction(function () use ($performance, $finalScore) {
            $payload = ['final_score' => $finalScore];

            if ($performance->status !== 'finished') {
                $payload['status'] = 'finished';
                $payload['performed_at'] = $performance->performed_at ?? now();
            }

            $performance->update($payload);

            $this->syncScheduleStatus($performance->schedule_id);
        });

        return redirect()
            ->route('admin.scores.edit', $performance)
            ->with('success', 'Nilai akhir tersimpan: ' . number_format($finalScore));
    }

    /**
     * Mark the art schedule as finished when all performers have been scored.
     */
    protected function syncScheduleStatus(int $scheduleId): void
    {
        $schedule = Schedule::find($scheduleId);

        if (! $schedule || $schedule->type !== 'art' || $schedule->status === 'cancelled') {
            return;
        }

        $performances = $schedule->performances()->get();

        if ($performances->isNotEmpty() && $performances->every(fn (Performance $item) => $item->status === 'finished')) {
            $schedule->status = 'finished';

            if (empty($schedule->end_time)) {
                $schedule->end_time = now()->format('H:i:s');
            }

            $schedule->save();
        }
    }
}
