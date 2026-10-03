<!--begin::Tambah Kategori Modal-->
<div class="modal fade" id="modal-add-category" tabindex="-1" aria-labelledby="modal-add-category-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-add-category-label">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new-category-name" class="form-label">Nama Kategori</label>
                        <input type="text" name="name" id="new-category-name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                            placeholder="Contoh: Dewasa Putra -58kg" required />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new-category-type" class="form-label">Jenis</label>
                            <select id="new-category-type" name="type"
                                class="form-select @error('type') is-invalid @enderror" required>
                                <option value="daeryun" @selected(old('type') === 'daeryun')>Daeryun</option>
                                <option value="art" @selected(old('type') === 'art')>Seni</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="new-category-gender" class="form-label">Gender</label>
                            <select id="new-category-gender" name="gender"
                                class="form-select @error('gender') is-invalid @enderror" required>
                                <option value="open" @selected(old('gender', 'open') === 'open')>Terbuka</option>
                                <option value="male" @selected(old('gender') === 'male')>Putra</option>
                                <option value="female" @selected(old('gender') === 'female')>Putri</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="new-category-age_class" class="form-label">Kelas Usia</label>
                        <input type="text" name="age_class" id="new-category-age_class"
                            class="form-control @error('age_class') is-invalid @enderror"
                            value="{{ old('age_class') }}" placeholder="Contoh: Dewasa / Junior / Senior" />
                        @error('age_class')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
<!--end::Tambah Kategori Modal-->
