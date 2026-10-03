<!--begin::Tambah Jadwal Modal-->
<div class="modal fade" id="modal-add-schedule" tabindex="-1" aria-labelledby="modal-add-schedule-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.schedules.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-add-schedule-label">Tambah Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="new-schedule-match_no" class="form-label">Kode Laga</label>
                            <input type="text" name="match_no" id="new-schedule-match_no"
                                class="form-control @error('match_no') is-invalid @enderror"
                                value="{{ old('match_no') }}" placeholder="Contoh: M-001" required />
                            @error('match_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new-schedule-category_id" class="form-label">Kategori</label>
                            <select id="new-schedule-category_id" name="category_id"
                                class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">— Pilih kategori —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new-schedule-arena_id" class="form-label">Arena</label>
                            <select id="new-schedule-arena_id" name="arena_id"
                                class="form-select @error('arena_id') is-invalid @enderror">
                                <option value="">— Belum ditentukan —</option>
                                @foreach ($arenas as $arena)
                                    <option value="{{ $arena->id }}" @selected(old('arena_id') == $arena->id)>
                                        {{ $arena->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('arena_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="new-schedule-type" class="form-label">Jenis</label>
                            <select id="new-schedule-type" name="type"
                                class="form-select @error('type') is-invalid @enderror" required>
                                <option value="daeryun" @selected(old('type') === 'daeryun')>Daeryun</option>
                                <option value="art" @selected(old('type') === 'art')>Seni</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="new-schedule-round" class="form-label">Ronde</label>
                            <input type="text" name="round" id="new-schedule-round"
                                class="form-control @error('round') is-invalid @enderror"
                                value="{{ old('round', 'penyisihan') }}" placeholder="penyisihan" />
                            @error('round')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="new-schedule-order_no" class="form-label">Urutan</label>
                            <input type="number" name="order_no" id="new-schedule-order_no" min="1"
                                class="form-control @error('order_no') is-invalid @enderror"
                                value="{{ old('order_no', 1) }}" />
                            @error('order_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="new-schedule-status" class="form-label">Status</label>
                            <select id="new-schedule-status" name="status"
                                class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'pending') === $status)>
                                        {{ \App\Models\Schedule::statusText($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new-schedule-match_date" class="form-label">Tanggal</label>
                            <input type="date" name="match_date" id="new-schedule-match_date"
                                class="form-control @error('match_date') is-invalid @enderror"
                                value="{{ old('match_date') }}" required />
                            @error('match_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new-schedule-start_time" class="form-label">Jam Mulai</label>
                            <input type="time" name="start_time" id="new-schedule-start_time"
                                class="form-control @error('start_time') is-invalid @enderror"
                                value="{{ old('start_time') }}" />
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new-schedule-end_time" class="form-label">Jam Selesai</label>
                            <input type="time" name="end_time" id="new-schedule-end_time"
                                class="form-control @error('end_time') is-invalid @enderror"
                                value="{{ old('end_time') }}" />
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Tambah Jadwal Modal-->
