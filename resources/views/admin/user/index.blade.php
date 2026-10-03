@extends('layouts.admin.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Daftar User</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.users.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" value="{{ $search }}" class="form-control"
                                    placeholder="Cari nama / email" style="width: 190px" />
                            </div>
                            <select name="role" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected($roleFilter === $role)>{{ $role }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('users.create')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-add-user" title="Tambah user baru">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah User
                                </button>
                            @endcan
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px">#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                @canany(['users.update', 'users.delete'])
                                    <th class="text-end">Aksi</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center me-2 flex-shrink-0"
                                                style="width: 32px; height: 32px; font-size: 0.85rem">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>
                                            <span class="fw-medium">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @forelse ($user->getRoleNames() as $roleName)
                                            <span class="badge bg-dark">{{ $roleName }}</span>
                                        @empty
                                            <span class="text-body-secondary">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at?->format('d M Y') }}</td>
                                    @canany(['users.update', 'users.delete'])
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-info" title="Lihat detail"
                                                    aria-label="Lihat detail user"
                                                    data-show-url="{{ route('admin.users.show', $user) }}">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                @can('users.update')
                                                    <button type="button" class="btn btn-outline-secondary" title="Ubah"
                                                        aria-label="Ubah user"
                                                        data-bs-toggle="modal" data-bs-target="#modal-edit-user"
                                                        data-url="{{ route('admin.users.update', $user) }}"
                                                        data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                                        data-role="{{ $user->getRoleNames()->first() }}"
                                                        data-active="{{ $user->is_active ? 1 : 0 }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('users.delete')
                                                    <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                        aria-label="Hapus user"
                                                        data-bs-toggle="modal" data-bs-target="#modal-delete-user"
                                                        data-url="{{ route('admin.users.destroy', $user) }}"
                                                        data-name="{{ $user->name }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    @endcanany
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-body-secondary py-4">
                                        Tidak ada user ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @include('admin.user.create')
        @include('admin.user.show')
        @include('admin.user.edit')
        @include('admin.user.delete')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('modal-edit-user');
            const deleteModalEl = document.getElementById('modal-delete-user');

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = editModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                form.querySelector('[name="name"]').value = trigger.dataset.name || '';
                form.querySelector('[name="email"]').value = trigger.dataset.email || '';
                form.querySelector('[name="role"]').value = trigger.dataset.role || '';
                form.querySelector('[name="password"]').value = '';
                form.querySelector('[name="is_active"]').checked = trigger.dataset.active === '1';
            });

            deleteModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = deleteModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                deleteModalEl.querySelector('[data-delete-name]').textContent = trigger.dataset.name || '';
            });

            document.querySelectorAll('[data-show-url]').forEach(function(button) {
                button.addEventListener('click', function() {
                    fetch(button.dataset.showUrl, { headers: { Accept: 'application/json' } })
                        .then(function(response) {
                            if (!response.ok) throw new Error('Not found');
                            return response.json();
                        })
                        .then(function(user) {
                            const modal = document.getElementById('modal-show-user');
                            modal.querySelector('[data-field="name"]').textContent = user.name;
                            modal.querySelector('[data-field="email"]').textContent = user.email;
                            modal.querySelector('[data-field="roles"]').textContent = user.roles.length ?
                                user.roles.join(', ') : '—';
                            modal.querySelector('[data-field="active"]').textContent = user.is_active ? 'Aktif' : 'Nonaktif';
                            modal.querySelector('[data-field="verified_at"]').textContent = user.verified_at ||
                                'Belum diverifikasi';
                            modal.querySelector('[data-field="created_at"]').textContent = user.created_at || '—';

                            new bootstrap.Modal(modal).show();
                        })
                        .catch(function() {
                            swalError('Gagal', 'Detail user tidak dapat dimuat.');
                        });
                });
            });

            @if ($errors->any())
                const errorModal = document.getElementById(
                    @json(old('user_id') ? 'modal-edit-user' : 'modal-add-user')
                );
                new bootstrap.Modal(errorModal).show();
            @endif
        });
    </script>
@endpush
