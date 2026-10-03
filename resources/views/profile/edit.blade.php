@extends('layouts.admin.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle text-primary"></i>
                        <h3 class="card-title mb-0">Informasi Profil</h3>
                    </div>
                    <div class="card-body">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-key text-primary"></i>
                        <h3 class="card-title mb-0">Ubah Kata Sandi</h3>
                    </div>
                    <div class="card-body">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                {{-- <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-person-x text-danger"></i>
                        <h3 class="card-title mb-0">Hapus Akun</h3>
                    </div>
                    <div class="card-body">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div> --}}
            </div>
        </div>
    </div>
@endsection
