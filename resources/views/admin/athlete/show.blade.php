<!--begin::Show Atlet Modal-->
<div class="modal fade" id="modal-show-athlete" tabindex="-1" aria-labelledby="modal-show-athlete-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-show-athlete-label">Detail Atlet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3 d-none">
                    <img data-field="photo" src="" alt="Foto atlet" class="rounded-circle"
                        style="width: 96px; height: 96px; object-fit: cover" />
                </div>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="text-body-secondary fw-normal" style="width: 35%">Nama</th>
                            <td data-field="name">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Jenis Kelamin</th>
                            <td data-field="gender">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Tanggal Lahir</th>
                            <td data-field="birth_date">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">No. Identitas</th>
                            <td data-field="id_number">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Kontingen</th>
                            <td data-field="contingent">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Daerah</th>
                            <td data-field="region">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Kategori</th>
                            <td>
                                <span data-field="category">—</span>
                                <span class="text-body-secondary small" data-field="category_type"></span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Nomor Peserta</th>
                            <td data-field="participant_number">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Kode QR</th>
                            <td data-field="qr_code">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Status</th>
                            <td data-field="status">—</td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">Dibuat</th>
                            <td data-field="created_at">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<!--end::Show Atlet Modal-->
