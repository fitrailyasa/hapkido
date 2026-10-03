<!--begin::Pinjam Perlengkapan Modal-->
<div class="modal fade" id="modal-borrow-equipment" tabindex="-1" aria-labelledby="modal-borrow-equipment-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.equipment-loans.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-borrow-equipment-label">Pinjam Perlengkapan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="borrow-athlete" class="form-label">Atlet</label>
                        <select id="borrow-athlete" name="athlete_id"
                            class="form-select @error('athlete_id') is-invalid @enderror" required>
                            <option value="">— Pilih atlet —</option>
                            @foreach ($athletes->groupBy(fn ($athlete) => $athlete->contingent?->name ?? '—') as $contingentName => $group)
                                <optgroup label="{{ $contingentName }}">
                                    @foreach ($group as $athlete)
                                        <option value="{{ $athlete->id }}"
                                            @selected((string) old('athlete_id') === (string) $athlete->id)>
                                            {{ $athlete->name }} ({{ $athlete->participant_number }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('athlete_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="borrow-equipment" class="form-label">Perlengkapan</label>
                        <select id="borrow-equipment" name="equipment_id"
                            class="form-select @error('equipment_id') is-invalid @enderror" required>
                            <option value="">— Pilih perlengkapan —</option>
                            @foreach ($equipments as $equipment)
                                <option value="{{ $equipment->id }}"
                                    data-available="{{ $equipment->available_qty }}"
                                    @selected((string) old('equipment_id') === (string) $equipment->id)
                                    @disabled($equipment->available_qty <= 0 || $equipment->status === 'maintenance')>
                                    {{ $equipment->code }} &middot; {{ $equipment->typeLabel() }}
                                    &middot; {{ $equipment->color }} {{ $equipment->size }}
                                    (tersedia {{ $equipment->available_qty }})
                                </option>
                            @endforeach
                        </select>
                        @error('equipment_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text" id="borrow-stock-hint">&nbsp;</div>
                    </div>

                    <div class="mb-3">
                        <label for="borrow-qty" class="form-label">Jumlah</label>
                        <input type="number" name="qty" id="borrow-qty" min="1" max="1000"
                            class="form-control @error('qty') is-invalid @enderror"
                            value="{{ old('qty', 1) }}" required />
                        @error('qty')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="borrow-notes" class="form-label">Catatan</label>
                        <textarea name="notes" id="borrow-notes" rows="2"
                            class="form-control @error('notes') is-invalid @enderror"
                            placeholder="Contoh: dipinjam untuk pertandingan partai 008">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Peminjaman</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Pinjam Perlengkapan Modal-->
