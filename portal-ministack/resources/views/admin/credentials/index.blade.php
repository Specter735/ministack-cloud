@extends('layouts.app')
@section('title', 'Manajemen Kredensial')
@section('content')
<div class="dashboard-wrapper">

    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="fa fa-key candy-text"></i> Manajemen Kredensial</h1>
            <p class="page-subtitle">
                Aktifkan atau cabut kunci akses IaaS pelanggan secara manual.
            </p>
        </div>
        <div class="page-badge">
            <i class="fa fa-user-shield"></i> Admin Panel
        </div>
    </section>

    @if (session('success'))
        <div class="alert" style="background: rgba(0,240,255,0.12); color:#00a8ba; border:1px solid rgba(0,240,255,0.25);">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-error">
            <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Stat cards --}}
    <section class="stats-grid">
        <div class="stat-card card-storage">
            <div class="stat-icon"><i class="fa fa-key"></i></div>
            <div class="stat-info">
                <div class="stat-label">Kredensial Aktif</div>
                <div class="stat-value">{{ $aktifCount }}</div>
                <p class="stat-note">Kunci akses yang sedang berjalan</p>
            </div>
        </div>
        <div class="stat-card card-cpu">
            <div class="stat-icon"><i class="fa fa-ban"></i></div>
            <div class="stat-info">
                <div class="stat-label">Kredensial Dicabut</div>
                <div class="stat-value">{{ $dicabutCount }}</div>
                <p class="stat-note">Kunci akses yang telah dinonaktifkan</p>
            </div>
        </div>
    </section>

    <section class="info-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
            <h2 class="panel-title" style="margin-bottom:0;">
                <i class="fa fa-list"></i> Daftar Kredensial
            </h2>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ route('admin.credentials.index', ['status' => 'all']) }}"
                   class="btn-secondary btn-small {{ $status === 'all' ? 'is-active' : '' }}">Semua</a>
                <a href="{{ route('admin.credentials.index', ['status' => 'Aktif']) }}"
                   class="btn-secondary btn-small {{ $status === 'Aktif' ? 'is-active' : '' }}">Aktif</a>
                <a href="{{ route('admin.credentials.index', ['status' => 'Dicabut']) }}"
                   class="btn-secondary btn-small {{ $status === 'Dicabut' ? 'is-active' : '' }}">Dicabut</a>
            </div>
        </div>

        @if ($credentials->isEmpty())
            <div class="coming-soon">
                <div class="cs-icon">🔑</div>
                <h3>Tidak Ada Data</h3>
                <p>Belum ada kredensial dengan filter "{{ $status }}" saat ini.</p>
            </div>
        @else
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Paket</th>
                            <th>Access Key ID</th>
                            <th>Bucket</th>
                            <th>Status Kunci</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($credentials as $credential)
                            <tr>
                                <td>#{{ $credential->id }}</td>
                                <td>
                                    {{ $credential->subscription->user->name ?? '-' }}
                                    <div style="font-weight:600; color:var(--text-light); font-size:0.8rem;">
                                        {{ $credential->subscription->user->email ?? '' }}
                                    </div>
                                </td>
                                <td>{{ $credential->subscription->plan->name ?? '-' }}</td>
                                <td>
                                    <code style="font-size:0.78rem; background:rgba(0,0,0,0.04); padding:2px 6px; border-radius:6px;">
                                        {{ $credential->access_key_id }}
                                    </code>
                                </td>
                                <td>
                                    <code style="font-size:0.78rem; background:rgba(0,0,0,0.04); padding:2px 6px; border-radius:6px;">
                                        {{ $credential->bucket_name ?? '-' }}
                                    </code>
                                </td>
                                <td>
                                    <span class="badge-soft {{ $credential->status_kunci === 'Aktif' ? 'cyan' : 'yellow' }}">
                                        {{ $credential->status_kunci }}
                                    </span>
                                </td>
                                <td>{{ $credential->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    @php
                                        $isAktif    = $credential->status_kunci === 'Aktif';
                                        $targetLabel = $isAktif ? 'Cabut' : 'Aktifkan';
                                        $confirmMsg  = $isAktif
                                            ? "Cabut kunci akses #{$credential->id} milik " . ($credential->subscription->user->name ?? 'pelanggan ini') . "? Pelanggan tidak dapat mengakses storage sampai diaktifkan kembali."
                                            : "Aktifkan kembali kunci akses #{$credential->id} milik " . ($credential->subscription->user->name ?? 'pelanggan ini') . "?";
                                    @endphp
                                    <form method="POST" action="{{ route('admin.credentials.toggle', $credential) }}"
                                          onsubmit="return confirm('{{ $confirmMsg }}');">
                                        @csrf
                                        <button type="submit"
                                                class="{{ $isAktif ? 'btn-secondary' : 'btn-primary' }} btn-small">
                                            <i class="fa {{ $isAktif ? 'fa-ban' : 'fa-check' }}"></i>
                                            {{ $targetLabel }}
                                        </button>
                                    </form>
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
