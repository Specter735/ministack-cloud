<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChromaStack — @yield('title', 'Cloud Platform')</title>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/chromastack.css') }}">

    @stack('styles')
</head>
<body>

    <!-- ── NAVBAR ── -->
    @auth
    <nav class="navbar">
        <div class="navbar-brand">
            <span class="brand-icon">🍬</span>
            <span class="brand-text">Chroma<span class="brand-accent">Stack</span></span>
        </div>
        <div class="navbar-menu">
            <a href="{{ route('dashboard') }}" class="navbar-link">Dashboard</a>
            <a href="{{ route('storage.index') }}" class="navbar-link">Storage</a>
            <a href="{{ route('credentials.index') }}" class="navbar-link">Kredensial</a>
            @if (Auth::user()->role === 'admin')
                <a href="{{ route('admin.payments.index') }}" class="navbar-link">
                    <i class="fa fa-user-shield"></i> Verifikasi Pembayaran
                </a>
            @endif
        </div>
        <div class="navbar-right">
            <span class="navbar-user">
                <i class="fa fa-user-circle"></i>
                {{ Auth::user()->name }}
            </span>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn-logout">
                    <i class="fa fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </nav>
    @endauth


    <!-- ── MAIN CONTENT ── -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- ── FOOTER ── -->
    <footer class="footer">
        <p>🍬 ChromaStack &copy; {{ date('Y') }} — Komputasi Awan Project · Universitas Lambung Mangkurat</p>
    </footer>

    @stack('scripts')
</body>
</html>