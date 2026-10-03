@extends('layouts.admin.app')

@section('title', 'Role & Permission')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Daftar Role</h3>
                @can('roles.create')
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-role">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Role
                    </button>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px">#</th>
                                <th>Nama Role</th>
                                <th style="width: 120px">Jumlah User</th>
                                <th style="width: 160px">Permission</th>
                                <th class="text-end" style="width: 200px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $role)
                                <tr>
                                    <td>{{ $role->id }}</td>
                                    <td class="fw-semibold">{{ $role->name }}</td>
                                    <td><span class="badge bg-secondary">{{ $role->users_count }}</span></td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis">{{ $role->permissions_count ?? $role->permissions()->count() }}</span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            @can('roles.permissions')
                                                <button type="button" class="btn btn-outline-primary" title="Kelola permission"
                                                    aria-label="Kelola permission role"
                                                    data-bs-toggle="modal" data-bs-target="#modal-permissions"
                                                    data-id="{{ $role->id }}" data-name="{{ $role->name }}">
                                                    <i class="bi bi-shield-check"></i>
                                                </button>
                                            @endcan
                                            @can('roles.update')
                                                <button type="button" class="btn btn-outline-secondary" title="Ubah nama"
                                                    aria-label="Ubah nama role"
                                                    data-bs-toggle="modal" data-bs-target="#modal-edit-role"
                                                    data-url="{{ route('admin.roles.update', $role) }}"
                                                    data-name="{{ $role->name }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('roles.delete')
                                                <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                    aria-label="Hapus role"
                                                    data-bs-toggle="modal" data-bs-target="#modal-delete-role"
                                                    data-url="{{ route('admin.roles.destroy', $role) }}"
                                                    data-name="{{ $role->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-4">Belum ada role.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Role -->
    <div class="modal fade" id="modal-add-role" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Nama Role</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="Contoh: Juri" required />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ubah Role -->
    <div class="modal fade" id="modal-edit-role" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Nama Role</label>
                        <input type="text" name="name" class="form-control" required />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Hapus Role -->
    <div class="modal fade" id="modal-delete-role" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Hapus Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        Yakin menghapus role <strong data-delete-name></strong>? Tindakan ini tidak dapat dibatalkan.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Permission -->
    <div class="modal fade" id="modal-permissions" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="#" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Permission: <span data-role-name></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body" style="max-height: 65vh; overflow-y: auto">
                        @foreach ($groups as $group => $items)
                            <div class="mb-3 border-bottom pb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="small text-uppercase">{{ $group }}</strong>
                                    <button type="button" class="btn btn-link btn-sm p-0" data-toggle-group>
                                        Pilih semua
                                    </button>
                                </div>
                                <div class="row">
                                    @foreach ($items as $permission => $label)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                                    value="{{ $permission }}" id="perm-{{ $loop->index }}-{{ $group }}"
                                                    data-permission />
                                                <label class="form-check-label"
                                                    for="perm-{{ $loop->index }}-{{ $group }}">
                                                    {{ $label }}
                                                    <span class="text-body-secondary small">({{ $permission }})</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Permission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addModal = document.getElementById('modal-add-role');
            const editModal = document.getElementById('modal-edit-role');
            const deleteModal = document.getElementById('modal-delete-role');
            const permModal = document.getElementById('modal-permissions');

            @if ($errors->any() && old('_open') === 'add-role')
                new bootstrap.Modal(addModal).show();
            @endif

            editModal.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;
                editModal.querySelector('form').setAttribute('action', trigger.dataset.url);
                editModal.querySelector('[name="name"]').value = trigger.dataset.name || '';
            });

            deleteModal.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;
                deleteModal.querySelector('form').setAttribute('action', trigger.dataset.url);
                deleteModal.querySelector('[data-delete-name]').textContent = trigger.dataset.name || '';
            });

            permModal.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = permModal.querySelector('form');
                form.setAttribute('action', '/admin/roles/' + trigger.dataset.id + '/permissions');
                permModal.querySelector('[data-role-name]').textContent = trigger.dataset.name;
                permModal.querySelectorAll('[data-permission]').forEach(function(box) {
                    box.checked = false;
                });

                fetch('/admin/roles/' + trigger.dataset.id + '/permissions', {
                        headers: { Accept: 'application/json' }
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        (data.permissions || []).forEach(function(name) {
                            const box = permModal.querySelector('[data-permission][value="' + name + '"]');
                            if (box) box.checked = true;
                        });
                    })
                    .catch(function() {
                        swalError('Gagal', 'Permission tidak dapat dimuat.');
                    });
            });

            permModal.querySelectorAll('[data-toggle-group]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const boxes = button.closest('.mb-3').querySelectorAll('[data-permission]');
                    const allChecked = Array.from(boxes).every(function(box) { return box.checked; });
                    boxes.forEach(function(box) { box.checked = !allChecked; });
                    button.textContent = allChecked ? 'Pilih semua' : 'Hapus semua';
                });
            });
        });
    </script>
@endpush
