<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Contingent;
use App\Models\Matchup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AthleteController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $contingentFilter = (string) $request->query('contingent');
        $categoryFilter = (string) $request->query('category');

        $query = Athlete::query()->with(['contingent', 'category'])->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('participant_number', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        if ($contingentFilter !== '') {
            $query->where('contingent_id', $contingentFilter);
        }

        if ($categoryFilter !== '') {
            $query->where('category_id', $categoryFilter);
        }

        return view('admin.athlete.index', [
            'athletes' => $query->paginate(10)->withQueryString(),
            'contingents' => Contingent::orderBy('name')->get(['id', 'name', 'code']),
            'categories' => Category::orderBy('name')->get(),
            'search' => $search,
            'contingentFilter' => $contingentFilter,
            'categoryFilter' => $categoryFilter,
        ]);
    }

    public function labels(Request $request)
    {
        $contingentFilter = (string) $request->query('contingent');

        $query = Athlete::query()->with(['contingent', 'category'])->orderBy('name');

        if ($contingentFilter !== '') {
            $query->where('contingent_id', $contingentFilter);
        }

        return view('admin.athlete.label', [
            'athletes' => $query->get(),
            'contingents' => Contingent::orderBy('name')->get(['id', 'name', 'code']),
            'contingentFilter' => $contingentFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'id_number' => ['nullable', 'string', 'max:255'],
            'contingent_id' => ['required', Rule::exists('contingents', 'id')],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'participant_number' => ['required', 'string', 'max:255', Rule::unique('athletes', 'participant_number')],
            'qr_code' => ['nullable', 'string', 'max:255', Rule::unique('athletes', 'qr_code')],
            'status' => ['required', 'in:active,inactive'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], [
            'participant_number.unique' => 'Nomor peserta sudah digunakan atlet lain.',
            'qr_code.unique' => 'Kode QR sudah digunakan atlet lain.',
        ]);

        $data['qr_code'] = $data['qr_code'] !== '' && $data['qr_code'] !== null
            ? $data['qr_code']
            : $data['participant_number'];

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('athletes', 'public');
        } else {
            unset($data['photo']);
        }

        Athlete::create($data);

        return redirect()
            ->route('admin.athletes.index')
            ->with('success', 'Atlet berhasil ditambahkan.');
    }

    public function show(Athlete $athlete)
    {
        $athlete->load(['contingent', 'category']);

        return response()->json([
            'id' => $athlete->id,
            'name' => $athlete->name,
            'gender' => $athlete->gender,
            'gender_label' => $athlete->genderLabel(),
            'birth_date' => optional($athlete->birth_date)->format('d M Y'),
            'age' => $athlete->birth_date?->age ?? null,
            'id_number' => $athlete->id_number ?? '—',
            'contingent' => $athlete->contingent
                ? $athlete->contingent->name . ' (' . $athlete->contingent->code . ')'
                : '—',
            'region' => $athlete->contingent?->region ?? '—',
            'category' => $athlete->category?->name ?? '—',
            'category_type' => $athlete->category?->typeLabel() ?? '—',
            'participant_number' => $athlete->participant_number,
            'qr_code' => $athlete->qr_code,
            'status' => $athlete->status,
            'status_label' => $athlete->statusLabel(),
            'photo_url' => $this->photoUrl($athlete->photo),
            'created_at' => optional($athlete->created_at)->format('d M Y, H:i'),
            'updated_at' => optional($athlete->updated_at)->format('d M Y, H:i'),
        ]);
    }

    public function update(Request $request, Athlete $athlete): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'id_number' => ['nullable', 'string', 'max:255'],
            'contingent_id' => ['required', Rule::exists('contingents', 'id')],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'participant_number' => ['required', 'string', 'max:255', Rule::unique('athletes', 'participant_number')->ignore($athlete->id)],
            'qr_code' => ['nullable', 'string', 'max:255', Rule::unique('athletes', 'qr_code')->ignore($athlete->id)],
            'status' => ['required', 'in:active,inactive'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], [
            'participant_number.unique' => 'Nomor peserta sudah digunakan atlet lain.',
            'qr_code.unique' => 'Kode QR sudah digunakan atlet lain.',
        ]);

        $data['qr_code'] = $data['qr_code'] !== '' && $data['qr_code'] !== null
            ? $data['qr_code']
            : $data['participant_number'];

        if ($request->hasFile('photo')) {
            if ($athlete->photo && ! Str::startsWith($athlete->photo, ['http://', 'https://'])) {
                Storage::disk('public')->delete($athlete->photo);
            }
            $data['photo'] = $request->file('photo')->store('athletes', 'public');
        } else {
            unset($data['photo']);
        }

        $athlete->update($data);

        return redirect()
            ->route('admin.athletes.index')
            ->with('success', 'Data atlet berhasil diperbarui.');
    }

    public function destroy(Athlete $athlete): RedirectResponse
    {
        $inMatch = Matchup::where('athlete_a_id', $athlete->id)
            ->orWhere('athlete_b_id', $athlete->id)
            ->exists();

        if ($inMatch || $athlete->performances()->exists()) {
            return back()->with('error', 'Atlet tidak bisa dihapus karena sudah terdaftar pada pertandingan.');
        }

        if ($athlete->photo && ! Str::startsWith($athlete->photo, ['http://', 'https://'])) {
            Storage::disk('public')->delete($athlete->photo);
        }

        $athlete->delete();

        return redirect()
            ->route('admin.athletes.index')
            ->with('success', 'Atlet berhasil dihapus.');
    }

    protected function photoUrl(?string $photo): ?string
    {
        if (! $photo) {
            return null;
        }

        if (Str::startsWith($photo, ['http://', 'https://'])) {
            return $photo;
        }

        return Storage::disk('public')->url($photo);
    }
}
