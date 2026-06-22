@extends('layouts.app')

@section('title', 'Storage')

@section('content')
<div class="dashboard-wrapper">

    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="fa fa-database candy-text"></i> Storage</h1>
            <p class="page-subtitle">
                Kelola paket penyimpanan, kuota storage, bucket, dan status layanan IaaS kamu.
            </p>
        </div>
        <div class="page-badge">
            <i class="fa fa-cloud"></i> MiniStack Storage
        </div>
    </section>

    @if (session('success'))
        <div class="alert" style="background: rgba(0, 240, 255, 0.12); color:#00a8ba; border:1px solid rgba(0,240,255,0.25);">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error">
            <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    <section class="stats-grid">
        <div class="stat-card card-storage">
            <div class="stat-icon">
                <i class="fa fa-box"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Paket Aktif</div>
                <div class="stat-value">{{ $activeSubscription->plan_name ?? 'Belum Ada' }}</div>
                <p class="stat-note">Status layanan storage kamu</p>
            </div>
        </div>

        <div class="stat-card card-cpu">
            <div class="stat-icon">
                <i class="fa fa-hard-drive"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Kuota</div>
                <div class="stat-value">
                    {{ $activeSubscription ? $activeSubscription->storage_quota_gb : 0 }}
                    <span>GB</span>
                </div>
                <p class="stat-note">Kapasitas penyimpanan aktif</p>
            </div>
        </div>

        <div class="stat-card card-ram">
            <div class="stat-icon">
                <i class="fa fa-chart-pie"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Terpakai</div>
                <div class="stat-value">
                    {{ number_format($usedStorageMb, 0) }}
                    <span>MB</span>
                </div>
                <p class="stat-note">{{ number_format($remainingStorageMb, 0) }} MB tersisa</p>
            </div>
        </div>
    </section>

    <section class="info-panel">
        <h2 class="panel-title">
            <i class="fa fa-gauge-high"></i> Penggunaan Storage
        </h2>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-key"><i class="fa fa-percent"></i> Persentase</span>
                <span class="info-val">{{ $usedPercent }}%</span>
                <div class="progress-bar-wrap">
                    <div class="progress-bar" style="width: {{ min($usedPercent, 100) }}%"></div>
                </div>
            </div>

            <div class="info-item">
                <span class="info-key"><i class="fa fa-bucket"></i> Bucket</span>
                <span class="info-val">{{ $bucket->bucket_name ?? 'Bucket belum tersedia' }}</span>
            </div>

            <div class="info-item">
                <span class="info-key"><i class="fa fa-key"></i> Status Kredensial</span>
                <span class="info-val">{{ $credential->status_kunci ?? 'Kredensial belum tersedia' }}</span>
            </div>
        </div>
    </section>

    <section class="info-panel">
        <h2 class="panel-title">
            <i class="fa fa-layer-group"></i> Paket Layanan Storage
        </h2>

        {{-- Banner guard: muncul jika user sudah punya subscription pending/active --}}
        @if ($blockCheckout)
            <div style="
                display:flex; align-items:flex-start; gap:1rem;
                background: rgba(255,193,7,0.1);
                border: 1px solid rgba(255,193,7,0.4);
                border-radius: 14px;
                padding: 1rem 1.25rem;
                margin-bottom: 1.5rem;
            ">
                <i class="fa fa-triangle-exclamation" style="color:#d97706; margin-top:2px; font-size:1.1rem;"></i>
                <div>
                    <strong style="color:#92400e;">Pengajuan Tidak Tersedia</strong>
                    <p style="margin:0.25rem 0 0; color:#78350f; font-size:0.9rem;">
                        {{ $blockReason }}
                        Pantau status pengajuanmu di tabel <strong>Riwayat Penyewaan</strong> di bawah.
                    </p>
                </div>
            </div>
        @endif

        <div class="plan-grid">
            @foreach ($plans as $plan)
                {{-- data-blocked dikirim ke JS agar alert-nya lebih informatif dari sisi client --}}
                <div class="plan-card">
                    <h3 class="plan-name">{{ $plan->name }}</h3>
                    <p class="plan-description">{{ $plan->description }}</p>

                    <div class="plan-price">
                        Rp{{ number_format($plan->price, 0, ',', '.') }}
                    </div>
                    <div class="plan-period">per bulan</div>

                    <div class="plan-features">
                        <div class="plan-feature">
                            <i class="fa fa-database"></i>
                            Storage {{ $plan->storage_quota_gb }} GB
                        </div>
                        <div class="plan-feature">
                            <i class="fa fa-box-archive"></i>
                            Maks. Bucket {{ $plan->max_buckets }}
                        </div>
                    </div>

                    @php
                        $isPlanSame   = $activeSubscription && (int) $activeSubscription->plan_id === (int) $plan->id;
                        $isActive     = $activeSubscription && $activeSubscription->status === 'active';
                        $showUpgrade  = $isActive && !$isPlanSame;   // active + beda paket → boleh upgrade
                        $showBlocked  = $blockCheckout && !$showUpgrade; // pending → blokir
                    @endphp

                    @if ($showBlocked)
                        {{-- Pending: tombol dikunci --}}
                        <div style="margin-top:1rem;">
                            <button type="button" class="btn-primary btn-full" disabled
                                    style="opacity:0.5; cursor:not-allowed;">
                                <i class="fa fa-lock"></i> Tidak Tersedia
                            </button>
                        </div>

                    @elseif ($isPlanSame)
                        {{-- Paket yang sedang aktif: tandai saja, tidak ada tombol --}}
                        <div style="margin-top:1rem;">
                            <button type="button" class="btn-primary btn-full" disabled
                                    style="opacity:0.6; cursor:default;">
                                <i class="fa fa-check-circle"></i> Paket Aktif Kamu
                            </button>
                        </div>

                    @elseif ($showUpgrade)
                        {{-- Active + paket berbeda: tampilkan tombol Upgrade --}}
                        <form class="upgrade-api-form" data-plan-id="{{ $plan->id }}" style="margin-top:1rem;">
                            <div style="margin-bottom:1rem;">
                                <label style="display:block; margin-bottom:0.45rem; font-weight:800; color:var(--text-dark);">
                                    Metode Bayar
                                </label>
                                <select name="metode_bayar" required
                                        style="width:100%; padding:0.8rem 1rem; border-radius:14px; border:1px solid #e2e8f0; font-weight:700; color:var(--text-dark);">
                                    <option value="">Pilih Metode Bayar</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Virtual Account">Virtual Account</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary btn-full"
                                    style="background: linear-gradient(135deg, #a855f7, #6366f1);">
                                <i class="fa fa-arrow-up"></i> Upgrade ke Paket Ini
                            </button>
                        </form>

                    @else
                        {{-- User baru: form checkout biasa (pola teman) --}}
                        <form class="checkout-api-form" data-plan-id="{{ $plan->id }}" style="margin-top: 1rem;">
                            <div style="margin-bottom: 1rem;">
                                <label style="display:block; margin-bottom:0.45rem; font-weight:800; color:var(--text-dark);">
                                    Metode Bayar
                                </label>
                                <select name="metode_bayar" required
                                        style="width:100%; padding:0.8rem 1rem; border-radius:14px; border:1px solid #e2e8f0; font-weight:700; color:var(--text-dark);">
                                    <option value="">Pilih Metode Bayar</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Virtual Account">Virtual Account</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary btn-full">
                                <i class="fa fa-cart-shopping"></i> Ajukan Sewa
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="info-panel">
        <h2 class="panel-title">
            <i class="fa fa-clock-rotate-left"></i> Riwayat Penyewaan
        </h2>

        @if ($subscriptions->isEmpty())
            <div class="coming-soon">
                <div class="cs-icon">🧾</div>
                <h3>Belum Ada Riwayat</h3>
                <p>Riwayat penyewaan akan muncul setelah kamu mengajukan paket storage.</p>
                <div class="cs-tags">
                    <span class="cs-tag">Checkout</span>
                    <span class="cs-tag">Payment</span>
                    <span class="cs-tag">Verifikasi</span>
                </div>
            </div>
        @else
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Status Sewa</th>
                            <th>Metode Bayar</th>
                            <th>Status Bayar</th>
                            <th>Mulai</th>
                            <th>Berakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->plan_name }}</td>
                                <td>
                                    <span class="badge-soft {{ $subscription->status === 'active' ? 'cyan' : 'yellow' }}">
                                        {{ $subscription->status }}
                                    </span>
                                </td>
                                <td>{{ $subscription->metode_bayar ?? '-' }}</td>
                                <td>{{ $subscription->status_bayar ?? '-' }}</td>
                                <td>{{ $subscription->subscribed_at }}</td>
                                <td>{{ $subscription->expires_at ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.checkout-api-form').forEach((form) => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const planId = this.dataset.planId;
            const metodeBayar = this.querySelector('select[name="metode_bayar"]').value;
            const token = localStorage.getItem('auth_token');

            if (!metodeBayar) {
                alert('Silakan pilih metode bayar terlebih dahulu.');
                return;
            }

            if (!token) {
                alert('Token API tidak ditemukan. Silakan login ulang terlebih dahulu.');
                return;
            }

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengajukan...';

            try {
                const response = await fetch('/api/iaas/checkout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                    },
                    body: JSON.stringify({
                        plan_id: planId,
                        metode_bayar: metodeBayar,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    // Guard server mengembalikan 422 dengan field 'message'
                    const message = data.message || data.error || 'Pengajuan sewa gagal.';
                    throw new Error(message);
                }

                alert(data.message || 'Pengajuan sewa berhasil dibuat.');
                window.location.reload();
            } catch (error) {
                alert(error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    });
    // ── Handler Upgrade Paket ──
    document.querySelectorAll('.upgrade-api-form').forEach((form) => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const planId      = this.dataset.planId;
            const metodeBayar = this.querySelector('select[name="metode_bayar"]').value;
            const token       = localStorage.getItem('auth_token');

            if (!metodeBayar) {
                alert('Silakan pilih metode bayar terlebih dahulu.');
                return;
            }

            if (!token) {
                alert('Token API tidak ditemukan. Silakan login ulang terlebih dahulu.');
                return;
            }

            const confirmed = confirm(
                'Upgrade paket akan menonaktifkan paket dan kredensial yang sedang aktif sekarang. ' +
                'Kamu perlu menunggu verifikasi admin sebelum paket baru aktif.\n\nLanjutkan?'
            );
            if (!confirmed) return;

            const submitButton  = this.querySelector('button[type="submit"]');
            const originalText  = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';

            try {
                const response = await fetch('/api/iaas/upgrade', {
                    method: 'POST',
                    headers: {
                        'Accept':        'application/json',
                        'Content-Type':  'application/json',
                        'Authorization': `Bearer ${token}`,
                    },
                    body: JSON.stringify({ plan_id: planId, metode_bayar: metodeBayar }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Upgrade gagal.');
                }

                alert(data.message || 'Permintaan upgrade berhasil diajukan.');
                window.location.reload();
            } catch (error) {
                alert(error.message);
            } finally {
                submitButton.disabled  = false;
                submitButton.innerHTML = originalText;
            }
        });
    });
</script>
@endpush