<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Services\BracketViewerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BracketController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('type', 'daeryun')->orderBy('name')->get();

        $requestedId = (int) $request->query('category', 0);
        $selected = ($requestedId > 0 ? $categories->firstWhere('id', $requestedId) : null)
            ?? $categories->first();

        $rounds = [];
        $hasBracket = false;
        $athleteCount = 0;
        $viewerData = null;
        $roundLabels = [];
        $matchUrls = [];
        $byeIds = [];
        $emptyIds = [];
        $champion = null;

        if ($selected) {
            $athleteCount = $selected->athletes()->where('status', 'active')->count();
            $viewer = app(BracketViewerService::class);
            $hasBracket = $viewer->hasBracket($selected);

            if ($hasBracket) {
                $rounds = $viewer->rounds($selected);

                $payload = $viewer->payload($selected, withAdminLinks: true);

                $viewerData = $payload['viewerData'];
                $roundLabels = $payload['roundLabels'];
                $matchUrls = $payload['matchUrls'];
                $byeIds = $payload['byeIds'];
                $emptyIds = $payload['emptyIds'];
                $champion = $payload['champion'];
            }
        }

        return view('admin.bracket.index', [
            'categories' => $categories,
            'selected' => $selected,
            'rounds' => $rounds,
            'hasBracket' => $hasBracket,
            'athleteCount' => $athleteCount,
            'viewerData' => $viewerData,
            'roundLabels' => $roundLabels,
            'matchUrls' => $matchUrls,
            'byeIds' => $byeIds,
            'emptyIds' => $emptyIds,
            'champion' => $champion,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            ],
            [
                'category_id.required' => 'Kelas wajib dipilih.',
                'category_id.exists' => 'Kelas tidak ditemukan.',
            ]
        );

        $category = Category::find($data['category_id']);

        if (! $category) {
            return back()->with('error', 'Kelas tidak ditemukan.');
        }

        if ($category->type !== 'daeryun') {
            return back()->with('error', 'Bracket hanya bisa dibuat untuk kelas Daeryun.');
        }

        $athleteCount = $category->athletes()->where('status', 'active')->count();

        if ($athleteCount < 2) {
            return back()->with('error', "Bracket gagal dibuat: peserta aktif pada kelas {$category->name} baru {$athleteCount} orang (minimal 2).");
        }

        DB::transaction(function () use ($category) {
            app(\App\Services\BracketService::class)->generate($category);
        });

        if (! BracketSlot::where('category_id', $category->id)->exists()) {
            return back()->with('error', "Bracket kelas {$category->name} gagal dibuat. Silakan periksa kembali data peserta.");
        }

        return redirect()
            ->route('admin.brackets.index', ['category' => $category->id])
            ->with('success', "Bracket kelas {$category->name} berhasil dibuat untuk {$athleteCount} peserta.");
    }
}
