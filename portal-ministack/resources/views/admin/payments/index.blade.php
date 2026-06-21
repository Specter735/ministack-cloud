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
        <div class="stat-card card-storage">
            <div class="stat-icon">
                <i class="fa fa-hourglass-half"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Menunggu Verifikasi</div>
                <div class="stat-value">{{ $pendingCount }}</div>
                <p class="stat-note">Nota pembayaran berstatus Pending</p>
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
                                        <form method="POST" action="{{ route('admin.payments.verify', $payment) }}"
                                              onsubmit="return confirm('ACC pembayaran #{{ $payment->id }} dari {{ $payment->subscription->user->name ?? 'pelanggan ini' }}? Infrastruktur IaaS akan langsung dialokasikan.');">
                                            @csrf
                                            <button type="submit" class="btn-primary btn-small">
                                                <i class="fa fa-check"></i> ACC
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge-soft cyan"><i class="fa fa-check"></i> Terverifikasi</span>
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
</style>
@endpush
