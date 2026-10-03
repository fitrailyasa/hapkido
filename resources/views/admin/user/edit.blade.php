<!--begin::Edit User Modal-->
<div class="modal fade" id="modal-edit-user" tabindex="-1" aria-labelledby="modal-edit-user-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ old('user_id') ? route('admin.users.update', old('user_id')) : '#' }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_id" value="{{ old('user_id') }}" />
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-edit-user-label">Ubah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-user-name" class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" id="edit-user-name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                            required />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit-user-email" class="form-label">Email</label>
                        <input type="email" name="email" id="edit-user-email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                            required />
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit-user-password" class="form-label">Password</label>
                        <input type="password" name="password" id="edit-user-password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Kosongkan jika tidak diganti" />
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit-user-role" class="form-label">Role</label>
                        <select id="edit-user-role" name="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="">— Tanpa role —</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit-user-active" />
                        <label class="form-check-label" for="edit-user-active">Akun aktif (dapat login)</label>
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
<!--end::Edit User Modal-->
