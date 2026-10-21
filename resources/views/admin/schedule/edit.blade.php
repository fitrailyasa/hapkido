<!--begin::Edit Schedule Modal-->
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
                            <label for="edit-schedule-match_no" class="form-label">Kode Laga <span class="text-danger">*</span></label>
                            <input type="text" name="match_no" id="edit-schedule-match_no"
                                class="form-control @error('match_no') is-invalid @enderror"
                                value="{{ old('match_no') }}" required />
                            @error('match_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit-schedule-category_id" class="form-label">Kelas <span class="text-danger">*</span></label>
                            <select id="edit-schedule-category_id" name="category_id"
                                class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">— Pilih kelas —</option>
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
                            <label for="edit-schedule-arena" class="form-label">Arena</label>
                            <input type="text" id="edit-schedule-arena" class="form-control" readonly
                                value="" placeholder="— Pilih kelas dulu —" />
                            <div class="form-text">Arena mengikuti kelas.</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit-schedule-type" class="form-label">Kategori <span class="text-danger">*</span></label>
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
                            <select id="edit-schedule-round" name="round" data-round-select
                                class="form-select @error('round') is-invalid @enderror">
                                @foreach (\App\Models\Schedule::ROUNDS as $value => $label)
                                    <option value="{{ $value }}"
                                        data-types="{{ $value === 'penampilan' ? 'art' : 'daeryun' }}"
                                        @selected(old('round') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
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
                            <label for="edit-schedule-status" class="form-label">Status <span class="text-danger">*</span></label>
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
                            <label for="edit-schedule-match_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
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
<!--end::Edit Schedule Modal-->
