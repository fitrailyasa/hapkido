<!--begin::Delete Jadwal Modal-->
<div class="modal fade" id="modal-delete-schedule" tabindex="-1" aria-labelledby="modal-delete-schedule-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-delete-schedule-label">Hapus Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        Apakah Anda yakin ingin menghapus jadwal
                        <strong data-delete-name>ini</strong>?
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Delete Jadwal Modal-->
