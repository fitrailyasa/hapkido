<!--begin::Edit Jadwal Modal-->
<div class="modal fade" id="modal-edit-schedule" tabindex="-1" aria-labelledby="modal-edit-schedule-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ old('schedule_id') ? route('admin.schedules.update', old('schedule_id')) : '#' }}"
                method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="schedule_id" value="{{ old('schedule_id') }}" />
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-edit-schedule-label">Ubah Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit-schedule-match_no" class="form-label">Kode Laga</label>
                            <input type="text" name="match_no" id="edit-schedule-match_no"
                                class="form-control @error('match_no') is-invalid @enderror"
                                value="{{ old('match_no') }}" required />
                            @error('match_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit-schedule-category_id" class="form-label">Kategori</label>
                            <select id="edit-schedule-category_id" name="category_id"
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
                            <label for="edit-schedule-arena_id" class="form-label">Arena</label>
                            <select id="edit-schedule-arena_id" name="arena_id"
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
                            <label for="edit-schedule-type" class="form-label">Jenis</label>
                            <select id="edit-schedule-type" name="type"
                                class="form-select @error('type') is-invalid @enderror" required>
                                <option value="daeryun" @selected(old('type') === 'daeryun')>Daeryun</option>
                                <option value="art" @selected(old('type') === 'art')>Seni</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit-schedule-round" class="form-label">Ronde</label>
                            <input type="text" name="round" id="edit-schedule-round"
                                class="form-control @error('round') is-invalid @enderror"
                                value="{{ old('round') }}" />
                            @error('round')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit-schedule-order_no" class="form-label">Urutan</label>
                            <input type="number" name="order_no" id="edit-schedule-order_no" min="1"
                                class="form-control @error('order_no') is-invalid @enderror"
                                value="{{ old('order_no', 1) }}" />
                            @error('order_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit-schedule-status" class="form-label">Status</label>
                            <select id="edit-schedule-status" name="status"
                                class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status') === $status)>
                                        {{ \App\Models\Schedule::statusText($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit-schedule-match_date" class="form-label">Tanggal</label>
                            <input type="date" name="match_date" id="edit-schedule-match_date"
                                class="form-control @error('match_date') is-invalid @enderror"
                                value="{{ old('match_date') }}" required />
                            @error('match_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit-schedule-start_time" class="form-label">Jam Mulai</label>
                            <input type="time" name="start_time" id="edit-schedule-start_time"
                                class="form-control @error('start_time') is-invalid @enderror"
                                value="{{ old('start_time') }}" />
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit-schedule-end_time" class="form-label">Jam Selesai</label>
                            <input type="time" name="end_time" id="edit-schedule-end_time"
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
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Edit Jadwal Modal-->
