<p class="text-body-secondary mb-3">
    Perbarui informasi profil dan alamat email akun Anda.
</p>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label for="profile-name" class="form-label">Nama Lengkap</label>
        <input id="profile-name" name="name" type="text"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="profile-email" class="form-label">Email</label>
        <input id="profile-email" name="email" type="email"
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $user->email) }}" required autocomplete="username" />
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="form-text">
                Alamat email Anda belum diverifikasi.
                <button type="submit" form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">
                    Klik di sini untuk mengirim ulang email verifikasi.
                </button>
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="form-text text-success">
                    Tautan verifikasi baru telah dikirim ke email Anda.
                </div>
            @endif
        @endif
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
        </button>

        @if (session('status') === 'profile-updated')
            <span class="text-body-secondary small" id="profile-saved">Tersimpan.</span>
        @endif
    </div>
</form>
