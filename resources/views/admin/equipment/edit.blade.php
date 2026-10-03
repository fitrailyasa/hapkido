<!--begin::Ubah Perlengkapan Modal-->
<div class="modal fade" id="modal-edit-equipment" tabindex="-1" aria-labelledby="modal-edit-equipment-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-edit-equipment-label">Ubah Perlengkapan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-equipment-type" class="form-label">Jenis</label>
                        <select id="edit-equipment-type" name="type" class="form-select" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-equipment-code" class="form-label">Kode</label>
                        <input type="text" name="code" id="edit-equipment-code" class="form-control" required />
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit-equipment-color" class="form-label">Warna</label>
                            <input type="text" name="color" id="edit-equipment-color" class="form-control" required />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-equipment-size" class="form-label">Ukuran</label>
                            <input type="text" name="size" id="edit-equipment-size" class="form-control" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit-equipment-qty" class="form-label">Total Stok</label>
                            <input type="number" name="total_qty" id="edit-equipment-qty" min="1" max="1000"
                                class="form-control" value="1" required />
                            <div class="form-text">Jumlah yang sedang dipinjam tidak dapat dikurangi.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-equipment-status" class="form-label">Status</label>
                            <select id="edit-equipment-status" name="status" class="form-select" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
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
<!--end::Ubah Perlengkapan Modal-->
