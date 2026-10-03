<!--begin::Import Jadwal Modal-->
<div class="modal fade" id="modal-import-schedule" tabindex="-1" aria-labelledby="modal-import-schedule-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.schedules.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-import-schedule-label">Import Jadwal (Excel)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="border rounded p-3 mb-3 small">
                        <div class="fw-semibold mb-1">Langkah mengisi template:</div>
                        <ol class="mb-2 ps-3 text-body-secondary">
                            <li>
                                Unduh <a href="{{ route('admin.schedules.template') }}">template Excel</a>
                                (berisi sheet <code>Template Jadwal</code> dan <code>Petunjuk Isi</code>).
                            </li>
                            <li>
                                Isi data pada sheet <code>Template Jadwal</code>, ikuti ketentuan tiap kolom
                                yang ada di sheet <code>Petunjuk Isi</code>.
                            </li>
                            <li>
                                Hapus semua baris contoh berawalan <code>CONTOH-</code> sebelum disimpan.
                            </li>
                            <li>
                                Simpan file dengan format <code>.xlsx</code>, lalu pilih file tersebut di bawah ini.
                            </li>
                        </ol>
                        <a href="{{ route('admin.schedules.template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Unduh Template
                        </a>
                    </div>
                    <div class="mb-3">
                        <label for="import-schedule-file" class="form-label">File Excel (.xlsx / .xls, maks 5 MB)</label>
                        <input type="file" name="file" id="import-schedule-file"
                            class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls" required />
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Import Jadwal Modal-->
