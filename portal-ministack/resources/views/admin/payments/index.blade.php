@extends('layouts.app')
@section('title', 'Verifikasi Pembayaran')
@section('content')
<div class="dashboard-wrapper">

    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="fa fa-circle-check candy-text"></i> Verifikasi Pembayaran</h1>
            <p class="page-subtitle">
                ACC nota pembayaran pelanggan untuk mengaktifkan kontrak sewa & mengalokasikan infrastruktur IaaS.
            </p>
        </div>
        <div class="page-badge">
            <i class="fa fa-user-shield"></i> Admin Panel
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
        <div class="stat-card card-ram">
            <div class="stat-icon">
                <i class="fa fa-hourglass-half"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Pembayaran Pending</div>
                <div class="stat-value">{{ $pendingCount }}</div>
                <p class="stat-note">Nota pembayaran yang menunggu verifikasi</p>
            </div>
        </div>

        <div class="stat-card card-cpu">
            <div class="stat-icon">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Pembayaran Lunas</div>
                <div class="stat-value">{{ $lunasCount }}</div>
                <p class="stat-note">Nota pembayaran yang sudah diverifikasi</p>
            </div>
        </div>

        <div class="stat-card card-storage">
            <div class="stat-icon">
                <i class="fa fa-ban"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Pembayaran Ditolak</div>
                <div class="stat-value">{{ $ditolakCount }}</div>
                <p class="stat-note">Nota pembayaran yang ditolak admin</p>
            </div>
        </div>
    </section>

    <section class="info-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
            <h2 class="panel-title" style="margin-bottom:0;">
                <i class="fa fa-list"></i> Daftar Nota Pembayaran
            </h2>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ route('admin.payments.index', ['status' => 'Pending']) }}"
                   class="btn-secondary btn-small {{ $status === 'Pending' ? 'is-active' : '' }}">Pending</a>
                <a href="{{ route('admin.payments.index', ['status' => 'Lunas']) }}"
                   class="btn-secondary btn-small {{ $status === 'Lunas' ? 'is-active' : '' }}">Lunas</a>
                <a href="{{ route('admin.payments.index', ['status' => 'Ditolak']) }}"
                   class="btn-secondary btn-small {{ $status === 'Ditolak' ? 'is-active' : '' }}">Ditolak</a>
                <a href="{{ route('admin.payments.index', ['status' => 'all']) }}"
                   class="btn-secondary btn-small {{ $status === 'all' ? 'is-active' : '' }}">Semua</a>
            </div>
        </div>

        @if ($payments->isEmpty())
            <div class="coming-soon">
                <div class="cs-icon">🧾</div>
                <h3>Tidak Ada Data</h3>
                <p>Tidak ada nota pembayaran dengan status "{{ $status }}" saat ini.</p>
            </div>
        @else
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Paket</th>
                            <th>Metode Bayar</th>
                            <th>Status Bayar</th>
                            <th>Status Sewa</th>
                            <th>Diajukan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>#{{ $payment->id }}</td>
                                <td>
                                    {{ $payment->subscription->user->name ?? '-' }}
                                    <div style="font-weight:600; color:var(--text-light); font-size:0.8rem;">
                                        {{ $payment->subscription->user->email ?? '' }}
                                    </div>
                                </td>
                                <td>{{ $payment->subscription->plan->name ?? '-' }}</td>
                                <td>{{ $payment->metode_bayar }}</td>
                                <td>
                                    <span class="badge-soft {{ $payment->status_bayar === 'Lunas' ? 'cyan' : 'yellow' }}">
                                        {{ $payment->status_bayar }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-soft {{ $payment->subscription->status === 'active' ? 'cyan' : 'yellow' }}">
                                        {{ $payment->subscription->status ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $payment->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    @if ($payment->status_bayar === 'Pending')
                                        <div class="payment-action-stack">
                                            <form method="POST"
                                                action="{{ route('admin.payments.verify', $payment) }}"
                                                class="payment-action-form"
                                                data-action="verify"
                                                data-payment-id="{{ $payment->id }}"
                                                data-customer="{{ $payment->subscription->user->name ?? 'pelanggan' }}"
                                                data-plan="{{ $payment->subscription->plan->name ?? '-' }}">
                                                @csrf

                                                <button type="submit" class="btn-primary btn-small payment-action-btn">
                                                    <i class="fa fa-check"></i> ACC
                                                </button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('admin.payments.reject', $payment) }}"
                                                class="payment-action-form"
                                                data-action="reject"
                                                data-payment-id="{{ $payment->id }}"
                                                data-customer="{{ $payment->subscription->user->name ?? 'pelanggan' }}"
                                                data-plan="{{ $payment->subscription->plan->name ?? '-' }}">
                                                @csrf

                                                <button type="submit" class="btn-secondary btn-small payment-action-btn payment-reject-btn">
                                                    <i class="fa fa-xmark"></i> Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="badge-soft {{ $payment->status_bayar === 'Lunas' ? 'cyan' : 'yellow' }}">
                                            <i class="fa fa-{{ $payment->status_bayar === 'Lunas' ? 'check' : 'xmark' }}"></i>
                                            {{ $payment->status_bayar === 'Lunas' ? 'Terverifikasi' : 'Ditolak' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection

@push('styles')
<style>
    .btn-secondary.is-active {
        background: var(--candy-pink);
        color: #fff;
        border-color: var(--candy-pink);
    }

    .payment-action-stack {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        align-items: flex-start;
    }

    .payment-action-stack form {
        margin: 0;
    }

    .payment-action-btn {
        min-width: 92px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }

    .payment-reject-btn {
        border-color: rgba(255, 46, 147, 0.38) !important;
        color: #ff2e93 !important;
        background: rgba(255, 46, 147, 0.08) !important;
    }

    .payment-reject-btn:hover {
        background: rgba(255, 46, 147, 0.16) !important;
        box-shadow: 0 8px 18px rgba(255, 46, 147, 0.16);
        transform: translateY(-1px);
    }

    .swal2-popup.glass-popup {
        border-radius: 20px !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.95) !important;
        box-shadow: 0 22px 60px rgba(17, 24, 39, 0.18) !important;
    }

    .swal2-title {
        color: var(--text-dark) !important;
        font-weight: 900 !important;
    }

    .swal2-html-container {
        color: var(--text-light) !important;
        line-height: 1.55 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.querySelectorAll('.payment-action-form').forEach((form) => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const action = this.dataset.action;
            const paymentId = this.dataset.paymentId;
            const customer = this.dataset.customer || 'pelanggan';
            const plan = this.dataset.plan || '-';

            const isVerify = action === 'verify';

            const confirmResult = await Swal.fire({
                title: isVerify ? 'ACC Pembayaran?' : 'Tolak Pembayaran?',
                html: isVerify
                    ? `Kamu akan menyetujui pembayaran <b>#${paymentId}</b> dari <b>${customer}</b> untuk paket <b>${plan}</b>.<br><br><span style="font-size:0.9em; color:#64748b;">Setelah di-ACC, penyewaan akan aktif dan kredensial IaaS akan dibuat.</span>`
                    : `Kamu akan menolak pembayaran <b>#${paymentId}</b> dari <b>${customer}</b> untuk paket <b>${plan}</b>.<br><br><span style="font-size:0.9em; color:#64748b;">Setelah ditolak, penyewaan akan dibatalkan dan pelanggan perlu mengajukan ulang.</span>`,
                icon: isVerify ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isVerify ? '#00a8ba' : '#ff2e93',
                cancelButtonColor: '#64748b',
                confirmButtonText: isVerify
                    ? '<i class="fa fa-check"></i> Ya, ACC Sekarang'
                    : '<i class="fa fa-xmark"></i> Ya, Tolak',
                cancelButtonText: '<i class="fa fa-times"></i> Batal',
                customClass: {
                    popup: 'glass-popup'
                }
            });

            if (!confirmResult.isConfirmed) {
                return;
            }

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';

            this.submit();
        });
    });
</script>
@endpush