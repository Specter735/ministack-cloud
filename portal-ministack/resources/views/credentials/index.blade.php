@extends('layouts.app')

@section('title', 'Kredensial')

@section('content')
<div class="dashboard-wrapper">

    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="fa fa-key candy-text"></i> Kredensial IaaS</h1>
            <p class="page-subtitle">
                Lihat Access Key, Secret Key, bucket, dan endpoint MiniStack untuk layanan storage kamu.
            </p>
        </div>
        <div class="page-badge">
            <i class="fa fa-shield-halved"></i> Secure Credential
        </div>
    </section>

    @if (!$activeSubscription)
        <section class="empty-state">
            <div class="empty-icon">🔐</div>
            <h2 class="empty-title">Belum Ada Paket Aktif</h2>
            <p class="empty-text">
                Kamu belum memiliki paket storage aktif. Silakan ajukan sewa storage terlebih dahulu
                agar sistem dapat membuat bucket dan kredensial layanan IaaS.
            </p>
            <a href="{{ route('storage.index') }}" class="btn-primary">
                <i class="fa fa-layer-group"></i> Lihat Paket Storage
            </a>
        </section>
    @elseif (!$credential)
        <section class="empty-state">
            <div class="empty-icon">⏳</div>
            <h2 class="empty-title">Kredensial Belum Tersedia</h2>
            <p class="empty-text">
                Paket aktif ditemukan, tetapi Access Key dan Secret Key belum dibuat.
                Biasanya kredensial muncul setelah pembayaran diverifikasi oleh admin.
            </p>
            <span class="badge-soft yellow">
                <i class="fa fa-circle-info"></i> Menunggu Verifikasi
            </span>
        </section>
    @else
        <section class="stats-grid">
            <div class="stat-card card-storage">
                <div class="stat-icon">
                    <i class="fa fa-box"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Paket Aktif</div>
                    <div class="stat-value">{{ $activeSubscription->plan_name }}</div>
                    <p class="stat-note">Layanan storage aktif</p>
                </div>
            </div>

            <div class="stat-card card-cpu">
                <div class="stat-icon">
                    <i class="fa fa-database"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Kuota</div>
                    <div class="stat-value">
                        {{ $activeSubscription->storage_quota_gb }}
                        <span>GB</span>
                    </div>
                    <p class="stat-note">Total kapasitas storage</p>
                </div>
            </div>

            <div class="stat-card card-ram">
                <div class="stat-icon">
                    <i class="fa fa-shield-halved"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Status Kunci</div>
                    <div class="stat-value">{{ $credential->status_kunci }}</div>
                    <p class="stat-note">Status access credential</p>
                </div>
            </div>
        </section>

        <section class="info-panel">
            <h2 class="panel-title">
                <i class="fa fa-lock"></i> Access Credential
            </h2>

            <div class="coming-soon" style="padding: 1.5rem; margin-bottom: 1.5rem;">
                <div class="cs-icon">⚠️</div>
                <h3>Jaga Secret Key</h3>
                <p>Jangan bagikan Secret Access Key kepada siapa pun karena kunci ini digunakan untuk mengakses layanan storage.</p>
            </div>

            <div class="info-grid" style="grid-template-columns: 1fr;">
                <div class="credential-row">
                    <div class="credential-head">
                        <span class="credential-label">
                            <i class="fa fa-server"></i> Endpoint
                        </span>
                        <button type="button" onclick="copyText('endpoint')" class="btn-secondary btn-small">Copy</button>
                    </div>
                    <code id="endpoint" class="code-box">{{ $endpoint }}</code>
                </div>

                <div class="credential-row">
                    <div class="credential-head">
                        <span class="credential-label">
                            <i class="fa fa-bucket"></i> Bucket Name
                        </span>
                        <button type="button" onclick="copyText('bucket')" class="btn-secondary btn-small">Copy</button>
                    </div>
                    <code id="bucket" class="code-box">{{ $bucket->bucket_name ?? $credential->bucket_name ?? '-' }}</code>
                </div>

                <div class="credential-row">
                    <div class="credential-head">
                        <span class="credential-label">
                            <i class="fa fa-id-card"></i> Access Key ID
                        </span>
                        <button type="button" onclick="copyText('accessKey')" class="btn-secondary btn-small">Copy</button>
                    </div>
                    <code id="accessKey" class="code-box">{{ $credential->access_key_id }}</code>
                </div>

                <div class="credential-row">
                    <div class="credential-head">
                        <span class="credential-label">
                            <i class="fa fa-user-secret"></i> Secret Access Key
                        </span>

                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <a href="{{ route('credentials.index', ['show_secret' => request()->boolean('show_secret') ? 0 : 1]) }}"
                               class="btn-secondary btn-small">
                                {{ request()->boolean('show_secret') ? 'Sembunyikan' : 'Tampilkan' }}
                            </a>

                            @if ($secretAccessKey)
                                <button type="button" onclick="copyText('secretKey')" class="btn-secondary btn-small">Copy</button>
                            @endif
                        </div>
                    </div>

                    @if ($secretAccessKey)
                        <code id="secretKey" class="code-box" style="border-color: rgba(255,46,147,0.25); background: rgba(255,46,147,0.06);">
                            {{ $secretAccessKey }}
                        </code>
                    @else
                        <code class="code-box">••••••••••••••••••••••••••••••••</code>
                    @endif
                </div>
            </div>
        </section>
    @endif

</div>
@endsection

@push('scripts')
<script>
    function copyText(id) {
        const element = document.getElementById(id);
        if (!element) return;

        navigator.clipboard.writeText(element.innerText.trim())
            .then(() => alert('Berhasil disalin.'))
            .catch(() => alert('Gagal menyalin teks.'));
    }
</script>
@endpush