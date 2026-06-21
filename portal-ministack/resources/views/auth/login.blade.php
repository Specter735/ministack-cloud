@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="auth-wrapper">

    <!-- Dekoratif background blob -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="auth-card glass">
        <!-- Logo -->
        <div class="auth-logo">
            <span class="logo-icon">🍬</span>
            <h1 class="logo-title">ChromaStack</h1>
            <p class="logo-subtitle">Cloud Platform · Candy Pop Edition</p>
        </div>

        <!-- Error Message -->
        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fa fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Login Form -->
        <form id="apiLoginForm" class="auth-form">
            @csrf

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
                    autofocus
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
                        placeholder="••••••••"
                        class="form-input"
                        required
                    >
                    <button type="button" class="toggle-pass" onclick="togglePassword('password', this)">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-check">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat saya</label>
            </div>

            <button type="submit" class="btn-primary btn-full">
                <i class="fa fa-rocket"></i> Masuk ke ChromaStack
            </button>
        </form>

        <div class="auth-footer">
            <p>Belum punya akun? <a href="{{ route('register') }}" class="link-candy">Daftar sekarang</a></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth.js') }}"></script>
@endpush