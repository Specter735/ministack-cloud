@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="dashboard-wrapper">

    <div class="welcome-banner glass">
        <div class="welcome-left">
            <div class="avatar-circle">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="welcome-title">Halo, {{ $user->name }}! 👋</h2>
                <p class="welcome-sub">Selamat datang di <strong>ChromaStack Cloud</strong></p>
                <span class="badge-package">
                    <i class="fa fa-box"></i> {{ $realData['package'] }}
                </span>
            </div>
        </div>
        <div class="welcome-right">
            <div class="uptime-badge">
                <i class="fa fa-circle" style="color:#a8ff78;"></i>
                Uptime: 99.9%
            </div>
        </div>
    </div>

    <div class="stats-grid">

        @php
            // Persentase pemakaian (0 kalau belum ada kuota sama sekali)
            $usagePercent = $realData['storage_total'] > 0
                ? ($realData['storage_used'] / $realData['storage_total']) * 100
                : 0;

            // Sisa kuota tidak boleh negatif walau pemakaian melebihi total (over-quota)
            $remainingMb = max(0, $realData['storage_total'] - $realData['storage_used']);
            $isOverQuota = $realData['storage_used'] > $realData['storage_total'];

            $barClass = $isOverQuota ? 'is-danger' : ($usagePercent >= 80 ? 'is-warning' : '');
        @endphp
        <div class="stat-card glass card-storage">
            <div class="stat-icon">
                <i class="fa fa-database"></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">Storage Terpakai</p>
                <p class="stat-value">{{ $realData['storage_used'] }} MB <span>/ {{ $realData['storage_total'] }} MB</span></p>
                <div class="progress-bar-wrap">
                    <div class="progress-bar {{ $barClass }}" style="width: {{ min(100, $usagePercent) }}%"></div>
                </div>
                @if ($isOverQuota)
                    <p class="stat-note is-danger"><i class="fa fa-triangle-exclamation"></i> Kuota terlampaui</p>
                @else
                    <p class="stat-note">{{ $remainingMb }} MB tersisa</p>
                @endif
            </div>
        </div>

        <div class="stat-card glass card-instance">
            <div class="stat-icon">
                <i class="fa fa-server"></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">S3 Buckets Aktif</p>
                <p class="stat-value">{{ $realData['buckets_count'] }} <span>Buckets</span></p>
                <p class="stat-note">Jumlah bucket penyimpanan aktif</p>
            </div>
        </div>

    </div>

    <div class="info-panel glass">
        <h3 class="panel-title">
            <i class="fa fa-info-circle"></i> Info Akun
        </h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-key"><i class="fa fa-user"></i> Nama</span>
                <span class="info-val">{{ $user->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-key"><i class="fa fa-envelope"></i> Email</span>
                <span class="info-val">{{ $user->email }}</span>
            </div>
            <div class="info-item">
                <span class="info-key"><i class="fa fa-calendar"></i> Bergabung</span>
                <span class="info-val">{{ $user->created_at->format('d M Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-key"><i class="fa fa-box"></i> Paket</span>
                <span class="info-val candy-text">{{ $realData['package'] }}</span>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert" style="background: rgba(0, 240, 255, 0.12); color:#00a8ba; border:1px solid rgba(0,240,255,0.25); margin-top: 20px;">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-error" style="margin-top: 20px;">
            <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Kartu checkout: tampil jika belum active, termasuk state pending (dengan banner peringatan) --}}
    @if (!$blockCheckout || ($blockCheckout && $blockReason && str_contains($blockReason, 'menunggu')))
        <div class="auth-card glass" style="margin-top: 20px;">
            <h3 class="panel-title"><i class="fa fa-shopping-cart"></i> Beli Paket IaaS</h3>

            @if ($blockCheckout)
                {{-- Banner pending --}}
                <div style="
                    display:flex; align-items:flex-start; gap:0.75rem;
                    background: rgba(255,193,7,0.1);
                    border: 1px solid rgba(255,193,7,0.4);
                    border-radius: 12px;
                    padding: 0.9rem 1.1rem;
                    margin-bottom: 1rem;
                ">
                    <i class="fa fa-hourglass-half" style="color:#d97706; margin-top:2px;"></i>
                    <div>
                        <strong style="color:#92400e;">Menunggu Verifikasi Admin</strong>
                        <p style="margin:0.2rem 0 0; color:#78350f; font-size:0.88rem;">
                            {{ $blockReason }} Pantau statusnya di halaman
                            <a href="{{ route('storage.index') }}" style="color:#d97706; font-weight:700;">Storage</a>.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-primary btn-full" disabled style="opacity:0.5; cursor:not-allowed;">
                    <i class="fa fa-lock"></i> Pengajuan Sedang Diproses
                </button>
            @else
                <p style="margin-bottom: 15px;">Anda belum memiliki paket. Silakan berlangganan untuk mulai membuat S3 Bucket.</p>

                <form method="POST" action="{{ route('storage.checkout') }}" class="auth-form">
                    @csrf
                    <div class="form-group">
                        <label for="plan_id">Pilih Paket:</label>
                        <select id="plan_id" name="plan_id" class="form-input" required>
                            @forelse ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->storage_quota_gb }} GB)</option>
                            @empty
                                <option value="" disabled selected>Tidak ada paket tersedia</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="metode_bayar">Metode Pembayaran:</label>
                        <select id="metode_bayar" name="metode_bayar" class="form-input" required>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Virtual Account">Virtual Account</option>
                            <option value="E-Wallet">E-Wallet</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary btn-full">
                        <i class="fa fa-rocket"></i> Pesan Sekarang
                    </button>
                </form>
            @endif
        </div>
    @endif

    <div class="coming-soon glass">
        <div class="cs-icon">🚀</div>
        <h3>MiniStack Integration — Coming Soon</h3>
        <p>Fase berikutnya: integrasi dengan MiniStack untuk manajemen S3 Bucket nyata.</p>
        <div class="cs-tags">
            <span class="cs-tag">OpenStack</span>
            <span class="cs-tag">MiniStack</span>
            <span class="cs-tag">REST API</span>
            <span class="cs-tag">S3 Storage</span>
        </div>
    </div>

</div>
@endsection