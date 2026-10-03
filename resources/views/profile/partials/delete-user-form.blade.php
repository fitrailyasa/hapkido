<p class="text-body-secondary mb-3">
    Setelah akun dihapus, seluruh sumber daya dan datanya akan dihapus secara permanen.
    Sebelum menghapus akun, unduh terlebih dahulu data atau informasi yang ingin Anda simpan.
</p>

<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-delete-account">
    <i class="bi bi-trash me-1"></i>Hapus Akun
</button>

<div class="modal fade" id="modal-delete-account" tabindex="-1" aria-labelledby="modal-delete-account-label"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-header">
                    <h5 class="modal-title" id="modal-delete-account-label">Yakin ingin menghapus akun Anda?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <p class="text-body-secondary mb-3">
                        Setelah akun dihapus, seluruh sumber daya dan datanya akan dihapus secara permanen.
                        Masukkan kata sandi Anda untuk mengonfirmasi penghapusan permanen.
                    </p>

                    <label for="delete-account-password" class="form-label">Kata Sandi</label>
                    <input id="delete-account-password" name="password" type="password"
                        class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                        placeholder="Masukkan kata sandi" autocomplete="current-password" />
                    @error('password', 'userDeletion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Hapus Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->userDeletion->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('modal-delete-account');
            if (modal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    </script>
@endif
