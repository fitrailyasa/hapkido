<p class="text-body-secondary mb-3">
    Pastikan akun Anda menggunakan kata sandi panjang dan acak agar tetap aman.
</p>

<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    @php($passwordErrors = $errors->updatePassword ?? null)

    <div class="mb-3">
        <label for="current-password" class="form-label">Kata Sandi Saat Ini</label>
        <input id="current-password" name="current_password" type="password"
            class="form-control {{ $passwordErrors?->has('current_password') ? 'is-invalid' : '' }}"
            autocomplete="current-password" />
        @if ($passwordErrors?->has('current_password'))
            <div class="invalid-feedback">{{ $passwordErrors->first('current_password') }}</div>
        @endif
    </div>

    <div class="mb-3">
        <label for="new-password" class="form-label">Kata Sandi Baru</label>
        <input id="new-password" name="password" type="password"
            class="form-control {{ $passwordErrors?->has('password') ? 'is-invalid' : '' }}"
            autocomplete="new-password" />
        @if ($passwordErrors?->has('password'))
            <div class="invalid-feedback">{{ $passwordErrors->first('password') }}</div>
        @endif
    </div>

    <div class="mb-3">
        <label for="confirm-password" class="form-label">Konfirmasi Kata Sandi</label>
        <input id="confirm-password" name="password_confirmation" type="password"
            class="form-control {{ $passwordErrors?->has('password_confirmation') ? 'is-invalid' : '' }}"
            autocomplete="new-password" />
        @if ($passwordErrors?->has('password_confirmation'))
            <div class="invalid-feedback">{{ $passwordErrors->first('password_confirmation') }}</div>
        @endif
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Simpan Kata Sandi
        </button>

        @if (session('status') === 'password-updated')
            <span class="text-body-secondary small">Tersimpan.</span>
        @endif
    </div>
</form>
