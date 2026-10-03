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

        $finalScore = $performance->final_score !== null
            ? (float) $performance->final_score
            : $this->average($judgeScores);

        return view('admin.score.edit', [
            'performance' => $performance,
            'judgeScores' => $judgeScores,
            'finalScore' => $finalScore,
        ]);
    }

    public function store(Request $request, Performance $performance): RedirectResponse
    {
        $data = $request->validate(
            [
                'score_1' => ['required', 'numeric', 'min:0', 'max:100'],
                'score_2' => ['required', 'numeric', 'min:0', 'max:100'],
                'score_3' => ['required', 'numeric', 'min:0', 'max:100'],
            ],
            [
                'score_1.required' => 'Nilai Juri 1 wajib diisi.',
                'score_2.required' => 'Nilai Juri 2 wajib diisi.',
                'score_3.required' => 'Nilai Juri 3 wajib diisi.',
                'score_1.numeric' => 'Nilai Juri 1 harus berupa angka.',
                'score_2.numeric' => 'Nilai Juri 2 harus berupa angka.',
                'score_3.numeric' => 'Nilai Juri 3 harus berupa angka.',
                'score_1.min' => 'Nilai Juri 1 minimal 0.',
                'score_2.min' => 'Nilai Juri 2 minimal 0.',
                'score_3.min' => 'Nilai Juri 3 minimal 0.',
                'score_1.max' => 'Nilai Juri 1 maksimal 100.',
                'score_2.max' => 'Nilai Juri 2 maksimal 100.',
                'score_3.max' => 'Nilai Juri 3 maksimal 100.',
            ]
        );

        $judgeScores = [
            1 => (float) $data['score_1'],
            2 => (float) $data['score_2'],
            3 => (float) $data['score_3'],
        ];

        $finalScore = $this->average($judgeScores);

        DB::transaction(function () use ($performance, $judgeScores, $finalScore) {
            foreach ($judgeScores as $judgeNo => $score) {
                Score::updateOrCreate(
                    ['performance_id' => $performance->id, 'judge_no' => $judgeNo],
                    ['score' => $score]
                );
            }

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
            ->with('success', 'Nilai juri tersimpan. Nilai akhir: ' . number_format($finalScore, 2));
    }

    /**
     * Nilai akhir = rata-rata 3 juri, dibulatkan 2 desimal.
     */
    protected function average(array $judgeScores): float
    {
        return round(array_sum($judgeScores) / count($judgeScores), 2);
    }

    /**
     * Tandai jadwal seni selesai bila seluruh penampil sudah dinilai.
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
