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

    </div> @if ($realData['package'] === 'Belum Berlangganan')
        <div class="auth-card glass" style="margin-top: 20px;">
            <h3 class="panel-title"><i class="fa fa-shopping-cart"></i> Beli Paket IaaS</h3>
            <p style="margin-bottom: 15px;">Anda belum memiliki paket. Silakan berlangganan untuk mulai membuat S3 Bucket.</p>
            
            <form id="checkoutForm" class="auth-form">
                <div class="form-group">
                    <label for="plan_id">Pilih Paket:</label>
                    <select id="plan_id" class="form-input" required>
                        <option value="1">Paket Mahasiswa (1 GB)</option>
                        <option value="2">Paket Profesional (50 GB)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="metode_bayar">Metode Pembayaran:</label>
                    <select id="metode_bayar" class="form-input" required>
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="Qris">QRIS / E-Wallet</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary btn-full">
                    <i class="fa fa-rocket"></i> Pesan Sekarang
                </button>
            </form>
            <div id="checkoutAlert" class="alert" style="display: none; margin-top: 15px;"></div>
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
@endsection @push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    const alertBox = document.getElementById('checkoutAlert');

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // Mencegah halaman termuat ulang

            // 1. Ambil nilai input dari formulir
            const planId = document.getElementById('plan_id').value;
            const metodeBayar = document.getElementById('metode_bayar').value;
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            
            // 2. Ambil Bearer Token dari Local Storage (dihasilkan saat Login)
            const token = localStorage.getItem('auth_token');

            if (!token) {
                showAlert('Sesi API tidak valid. Silakan logout dan login kembali.', 'error');
                return;
            }

            // 3. Ubah antarmuka tombol menjadi status memuat (loading)
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';
            alertBox.style.display = 'none';

            try {
                // 4. Kirim data ke Backend API
                const response = await axios.post('/api/iaas/checkout', {
                    plan_id: planId,
                    metode_bayar: metodeBayar
                }, {
                    headers: {
                        'Authorization': `Bearer ${token}` // Sisipkan token di sini
                    }
                });

                // 5. Tampilkan pesan berhasil
                showAlert(response.data.message || 'Checkout berhasil! Menunggu verifikasi Admin.', 'success');
                
                // Opsional: Muat ulang halaman setelah 2 detik agar status berubah
                setTimeout(() => {
                    window.location.reload();
                }, 2000);

            } catch (error) {
                // 6. Tampilkan pesan galat
                const errorMsg = error.response?.data?.message || 'Gagal memproses pesanan. Periksa koneksi Anda.';
                showAlert(errorMsg, 'error');
            } finally {
                // 7. Kembalikan tombol ke keadaan semula
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    // Fungsi pembantu untuk merender kotak peringatan (alert)
    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.style.display = 'block';
        alertBox.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
        alertBox.style.color = type === 'success' ? '#155724' : '#721c24';
        alertBox.style.padding = '10px';
        alertBox.style.borderRadius = '8px';
    }
});
</script>
@endpush