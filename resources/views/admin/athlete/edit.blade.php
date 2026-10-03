<!--begin::Edit Atlet Modal-->
<div class="modal fade" id="modal-edit-athlete" tabindex="-1" aria-labelledby="modal-edit-athlete-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ old('athlete_id') ? route('admin.athletes.update', old('athlete_id')) : '#' }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="athlete_id" value="{{ old('athlete_id') }}" />
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-edit-athlete-label">Ubah Atlet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-name" class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" id="edit-athlete-name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-participant_number" class="form-label">Nomor Peserta</label>
                            <input type="text" name="participant_number" id="edit-athlete-participant_number"
                                class="form-control @error('participant_number') is-invalid @enderror"
                                value="{{ old('participant_number') }}" required />
                            @error('participant_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-gender" class="form-label">Jenis Kelamin</label>
                            <select id="edit-athlete-gender" name="gender"
                                class="form-select @error('gender') is-invalid @enderror" required>
                                <option value="male" @selected(old('gender') === 'male')>Putra</option>
                                <option value="female" @selected(old('gender') === 'female')>Putri</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-birth_date" class="form-label">Tanggal Lahir</label>
                            <input type="date" name="birth_date" id="edit-athlete-birth_date"
                                class="form-control @error('birth_date') is-invalid @enderror"
                                value="{{ old('birth_date') }}" />
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-id_number" class="form-label">No. Identitas (KTP/Kartu Pelajar)</label>
                            <input type="text" name="id_number" id="edit-athlete-id_number"
                                class="form-control @error('id_number') is-invalid @enderror"
                                value="{{ old('id_number') }}" />
                            @error('id_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-contingent_id" class="form-label">Kontingen</label>
                            <select id="edit-athlete-contingent_id" name="contingent_id"
                                class="form-select @error('contingent_id') is-invalid @enderror" required>
                                <option value="">— Pilih kontingen —</option>
                                @foreach ($contingents as $contingent)
                                    <option value="{{ $contingent->id }}" @selected(old('contingent_id') == $contingent->id)>
                                        {{ $contingent->name }} ({{ $contingent->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('contingent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-category_id" class="form-label">Kategori</label>
                            <select id="edit-athlete-category_id" name="category_id"
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
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-qr_code" class="form-label">Kode QR</label>
                            <input type="text" name="qr_code" id="edit-athlete-qr_code"
                                class="form-control @error('qr_code') is-invalid @enderror"
                                value="{{ old('qr_code') }}" placeholder="Kosongkan untuk memakai nomor peserta" />
                            @error('qr_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-status" class="form-label">Status</label>
                            <select id="edit-athlete-status" name="status"
                                class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-athlete-photo" class="form-label">Ganti Foto (opsional)</label>
                            <input type="file" name="photo" id="edit-athlete-photo"
                                class="form-control @error('photo') is-invalid @enderror" accept="image/*" />
                            @error('photo')
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
<!--end::Edit Atlet Modal-->
