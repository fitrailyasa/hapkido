<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shared plumbing for the "bulk delete" action of every admin table.
 *
 * The table posts a list of row ids, each controller reuses the guards of its
 * single delete action and reports what was deleted and what was skipped.
 */
trait BulkDeletes
{
    /**
     * The validated list of ids posted by the bulk delete form.
     *
     * @return array<int, int>
     */
    protected function bulkIds(Request $request, string $table): array
    {
        $data = $request->validate(
            [
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', "exists:{$table},id"],
            ],
            [
                'ids.required' => 'Pilih minimal satu baris untuk dihapus.',
                'ids.min' => 'Pilih minimal satu baris untuk dihapus.',
                'ids.*.integer' => 'Data yang dipilih tidak valid.',
                'ids.*.exists' => 'Data yang dipilih tidak valid.',
            ]
        );

        return array_map('intval', $data['ids']);
    }

    /**
     * Build the redirect message of a bulk delete run.
     *
     * @param  array<int, string>  $skipped
     */
    protected function bulkResult(string $route, int $deleted, array $skipped): RedirectResponse
    {
        $skipped = collect($skipped)->unique()->values();

        if ($deleted === 0) {
            return redirect()
                ->route($route)
                ->with('error', 'Tidak ada data yang dihapus. ' . $this->skipSummary($skipped));
        }

        $message = "{$deleted} data berhasil dihapus.";

        if ($skipped->isNotEmpty()) {
            $message .= ' ' . count($skipped) . ' lainnya dilewati: ' . $this->skipSummary($skipped);
        }

        return redirect()->route($route)->with('success', $message);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $skipped
     */
    protected function skipSummary($skipped): string
    {
        $summary = $skipped->take(3)->implode(' ');

        if ($skipped->count() > 3) {
            $summary .= ' (+' . ($skipped->count() - 3) . ' lainnya)';
        }

        return $summary;
    }

    /**
     * The signed in user, when a controller needs to protect its own account.
     */
    protected function currentUserId(): int
    {
        $user = auth()->user();

        return $user instanceof Authenticatable ? (int) $user->getAuthIdentifier() : 0;
    }
}
