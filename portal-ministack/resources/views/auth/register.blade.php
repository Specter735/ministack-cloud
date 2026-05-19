@extends('layouts.app')
@section('title', 'Register')

@section('content')
<div class="auth-wrapper">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="auth-card glass">
        <div class="auth-logo">
            <span class="logo-icon">✨</span>
            <h1 class="logo-title">Buat Akun</h1>
            <p class="logo-subtitle">Bergabung di ChromaStack Cloud</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fa fa-exclamation-circle"></i>
                <ul style="margin:0; padding-left:1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="name">
                    <i class="fa fa-user"></i> Nama Lengkap
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Nama kamu"
                    class="form-input @error('name') is-error @enderror"
                    required
                    autofocus
                >
                @error('name')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">
                    <i class="fa fa-envelope"></i> Email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="kamu@chromastack.cloud"
                    class="form-input @error('email') is-error @enderror"
                    required
                >
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fa fa-lock"></i> Password
                </label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Min. 8 karakter"
                        class="form-input @error('password') is-error @enderror"
                        required
                    >
                    <button type="button" class="toggle-pass" onclick="togglePassword('password', this)">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">
                    <i class="fa fa-lock"></i> Konfirmasi Password
                </label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Ulangi password"
                        class="form-input"
                        required
                    >
                    <button type="button" class="toggle-pass" onclick="togglePassword('password_confirmation', this)">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary btn-full">
                <i class="fa fa-star"></i> Buat Akun ChromaStack
            </button>
        </form>

        <div class="auth-footer">
            <p>Sudah punya akun? <a href="{{ route('login') }}" class="link-candy">Login di sini</a></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}
</script>
@endpush