@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="dashboard-wrapper">

    <!-- ── WELCOME BANNER ── -->
    <div class="welcome-banner glass">
        <div class="welcome-left">
            <div class="avatar-circle">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="welcome-title">Halo, {{ $user->name }}! 👋</h2>
                <p class="welcome-sub">Selamat datang di <strong>ChromaStack Cloud</strong></p>
                <span class="badge-package">
                    <i class="fa fa-box"></i> {{ $dummyData['package'] }}
                </span>
            </div>
        </div>
        <div class="welcome-right">
            <div class="uptime-badge">
                <i class="fa fa-circle" style="color:#a8ff78;"></i>
                Uptime: {{ $dummyData['uptime'] }}
            </div>
        </div>
    </div>

    <!-- ── STATS GRID ── -->
    <div class="stats-grid">

        <!-- Storage Card -->
        <div class="stat-card glass card-storage">
            <div class="stat-icon">
                <i class="fa fa-database"></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">Storage Terpakai</p>
                <p class="stat-value">{{ $dummyData['storage_used'] }} <span>/ {{ $dummyData['storage_total'] }} GB</span></p>
                <div class="progress-bar-wrap">
                    <div class="progress-bar" style="width: {{ ($dummyData['storage_used'] / $dummyData['storage_total']) * 100 }}%"></div>
                </div>
                <p class="stat-note">{{ $dummyData['storage_total'] - $dummyData['storage_used'] }} GB tersisa</p>
            </div>
        </div>

        <!-- vCPU Card -->
        <div class="stat-card glass card-cpu">
            <div class="stat-icon">
                <i class="fa fa-microchip"></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">vCPU</p>
                <p class="stat-value">{{ $dummyData['vcpu'] }} <span>Core</span></p>
                <p class="stat-note">Virtual CPU tersedia</p>
            </div>
        </div>

        <!-- RAM Card -->
        <div class="stat-card glass card-ram">
            <div class="stat-icon">
                <i class="fa fa-memory"></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">RAM</p>
                <p class="stat-value">{{ $dummyData['ram'] }} <span>GB</span></p>
                <p class="stat-note">Memory dialokasikan</p>
            </div>
        </div>

        <!-- Instances Card -->
        <div class="stat-card glass card-instance">
            <div class="stat-icon">
                <i class="fa fa-server"></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">Instances Aktif</p>
                <p class="stat-value">{{ $dummyData['instances'] }} <span>Running</span></p>
                <p class="stat-note">Virtual machine aktif</p>
            </div>
        </div>

    </div>

    <!-- ── INFO PANEL ── -->
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
                <span class="info-val candy-text">{{ $dummyData['package'] }}</span>
            </div>
        </div>
    </div>

    <!-- ── COMING SOON ── -->
    <div class="coming-soon glass">
        <div class="cs-icon">🚀</div>
        <h3>MiniStack Integration — Coming Soon</h3>
        <p>Fase berikutnya: integrasi dengan MiniStack untuk manajemen instance nyata.</p>
        <div class="cs-tags">
            <span class="cs-tag">OpenStack</span>
            <span class="cs-tag">MiniStack</span>
            <span class="cs-tag">REST API</span>
            <span class="cs-tag">Keystone Auth</span>
        </div>
    </div>

</div>
@endsection