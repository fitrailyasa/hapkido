<!--begin::Tambah Kontingen Modal-->
<div class="modal fade" id="modal-add-contingent" tabindex="-1" aria-labelledby="modal-add-contingent-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.contingents.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-add-contingent-label">Tambah Kontingen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new-contingent-name" class="form-label">Nama Kontingen</label>
                        <input type="text" name="name" id="new-contingent-name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                            placeholder="Contoh: Kontingen Jawa Barat" required />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new-contingent-code" class="form-label">Kode</label>
                        <input type="text" name="code" id="new-contingent-code"
                            class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                            placeholder="Contoh: JABAR" required />
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new-contingent-region" class="form-label">Daerah</label>
                        <input type="text" name="region" id="new-contingent-region"
                            class="form-control @error('region') is-invalid @enderror" value="{{ old('region') }}"
                            placeholder="Opsional" />
                        @error('region')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new-contingent-coach_name" class="form-label">Nama Pelatih</label>
                        <input type="text" name="coach_name" id="new-contingent-coach_name"
                            class="form-control @error('coach_name') is-invalid @enderror"
                            value="{{ old('coach_name') }}" placeholder="Opsional" />
                        @error('coach_name')
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
<!--end::Tambah Kontingen Modal-->
