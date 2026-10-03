<!--begin::Edit Kontingen Modal-->
<div class="modal fade" id="modal-edit-contingent" tabindex="-1" aria-labelledby="modal-edit-contingent-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ old('contingent_id') ? route('admin.contingents.update', old('contingent_id')) : '#' }}"
                method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="contingent_id" value="{{ old('contingent_id') }}" />
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-edit-contingent-label">Ubah Kontingen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-contingent-name" class="form-label">Nama Kontingen</label>
                        <input type="text" name="name" id="edit-contingent-name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                            required />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit-contingent-code" class="form-label">Kode</label>
                        <input type="text" name="code" id="edit-contingent-code"
                            class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                            required />
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit-contingent-region" class="form-label">Daerah</label>
                        <input type="text" name="region" id="edit-contingent-region"
                            class="form-control @error('region') is-invalid @enderror" value="{{ old('region') }}" />
                        @error('region')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit-contingent-coach_name" class="form-label">Nama Pelatih</label>
                        <input type="text" name="coach_name" id="edit-contingent-coach_name"
                            class="form-control @error('coach_name') is-invalid @enderror"
                            value="{{ old('coach_name') }}" />
                        @error('coach_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
<!--end::Edit Kontingen Modal-->
