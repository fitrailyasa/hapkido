<!--begin::Tambah Perlengkapan Modal-->
<div class="modal fade" id="modal-add-equipment" tabindex="-1" aria-labelledby="modal-add-equipment-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.equipments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-add-equipment-label">Tambah Perlengkapan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new-equipment-type" class="form-label">Jenis</label>
                        <select id="new-equipment-type" name="type"
                            class="form-select @error('type') is-invalid @enderror" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new-equipment-code" class="form-label">Kode</label>
                        <input type="text" name="code" id="new-equipment-code"
                            class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                            placeholder="Contoh: HG-M-01" required />
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new-equipment-color" class="form-label">Warna</label>
                            <input type="text" name="color" id="new-equipment-color"
                                class="form-control @error('color') is-invalid @enderror" value="{{ old('color') }}"
                                placeholder="Merah / Biru" required />
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="new-equipment-size" class="form-label">Ukuran</label>
                            <input type="text" name="size" id="new-equipment-size"
                                class="form-control @error('size') is-invalid @enderror" value="{{ old('size') }}"
                                placeholder="S / M / L" required />
                            @error('size')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new-equipment-qty" class="form-label">Total Stok</label>
                            <input type="number" name="total_qty" id="new-equipment-qty" min="1" max="1000"
                                class="form-control @error('total_qty') is-invalid @enderror"
                                value="{{ old('total_qty', 1) }}" required />
                            @error('total_qty')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="new-equipment-status" class="form-label">Status</label>
                            <select id="new-equipment-status" name="status"
                                class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'available') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
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
<!--end::Tambah Perlengkapan Modal-->
