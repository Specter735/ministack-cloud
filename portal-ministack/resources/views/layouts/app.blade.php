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
                    <i class="fa fa-circle-check"></i> Verifikasi Pembayaran
                </a>
                <a href="{{ route('admin.credentials.index') }}" class="navbar-link">
                    <i class="fa fa-key"></i> Kelola Kredensial
                </a>
            @endif
        </div>
        <div class="navbar-right">
            <span class="navbar-user">
                <i class="fa fa-user-circle"></i>
                {{ Auth::user()->name }}
            </span>
            <form method="POST"
                action="{{ route('logout') }}"
                class="logout-confirm-form"
                style="display:inline;">
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


    <script>
    function loadSweetAlertIfNeeded() {
        return new Promise((resolve, reject) => {
            if (window.Swal) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

        document.querySelectorAll('.logout-confirm-form').forEach((form) => {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                try {
                    await loadSweetAlertIfNeeded();

                    const confirmResult = await Swal.fire({
                        title: 'Keluar dari Akun?',
                        html: 'Apakah kamu yakin ingin logout dari akun ini?<br><br><span style="font-size:0.9em; color:#64748b;">Kamu perlu login kembali untuk mengakses dashboard ChromaStack.</span>',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#ff2e93',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: '<i class="fa fa-sign-out-alt"></i> Ya, Logout',
                        cancelButtonText: '<i class="fa fa-times"></i> Batal',
                        customClass: {
                            popup: 'glass-popup'
                        }
                    });

                    if (!confirmResult.isConfirmed) {
                        return;
                    }

                    const submitButton = form.querySelector('button[type="submit"]');
                    const originalText = submitButton.innerHTML;

                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Keluar...';

                    form.submit();
                } catch (error) {
                    form.submit();
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>