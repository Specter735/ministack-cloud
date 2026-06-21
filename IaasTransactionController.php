<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSubscription;
use App\Models\Payment;
use App\Models\Credential;
use App\Models\ActivityLog;
use App\Services\PaymentVerificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Throwable;

class IaasTransactionController extends Controller
{
    /**
     * Objek layanan untuk verifikasi pembayaran & alokasi infrastruktur IaaS.
     * Logikanya dipakai bersama dengan halaman Admin (lihat AdminPaymentController).
     */
    protected $paymentVerificationService;

    public function __construct(PaymentVerificationService $paymentVerificationService)
    {
        $this->paymentVerificationService = $paymentVerificationService;
    }

    /**
     * ALUR 1: Pengguna memilih paket layanan dan melakukan checkout (Status: Pending)
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'metode_bayar' => 'required|string'
        ]);

        // Memulai transaksi basis data untuk menjamin integritas relasional
        DB::beginTransaction();
        try {
            // 1. Mencatat kontrak sewa baru dengan status 'pending' sesuai alur pembayaran
            $subscription = UserSubscription::create([
                'user_id' => Auth::id(),
                'plan_id' => $request->plan_id,
                'subscribed_at' => now(),
                'expires_at' => now()->addMonth(), // Asumsi masa aktif 1 bulan
                'status' => 'pending'
            ]);

            // 2. Membuat nota pembayaran yang harus dilunasi
            $payment = Payment::create([
                'subscription_id' => $subscription->id,
                'metode_bayar' => $request->metode_bayar,
                'status_bayar' => 'Pending'
            ]);

            // 3. Mencatat aktivitas ke dalam log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Checkout Paket',
                'description' => 'Pengguna mengajukan penyewaan paket ID ' . $request->plan_id
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Proses checkout berhasil. Silakan selesaikan pembayaran.',
                'payment_id' => $payment->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Kegagalan sistem saat memproses checkout: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ALUR 2: Admin memverifikasi pembayaran dan sistem mengalokasikan infrastruktur otomatis
     */
    public function verifyPayment($paymentId)
    {
        // Validasi pembatasan akses khusus Admin
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Akses ditolak. Tindakan ini memerlukan otorisasi administrator.'], 403);
        }

        try {
            $this->paymentVerificationService->verify((int) $paymentId, Auth::id());

            return response()->json(['message' => 'Verifikasi berhasil. Infrastruktur IaaS siap digunakan oleh pelanggan.'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Nota pembayaran tidak ditemukan.'], 404);
        } catch (RuntimeException $e) {
            // Contoh: pembayaran sudah Lunas sebelumnya
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Kegagalan alokasi infrastruktur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ALUR 3: Admin mencabut atau mengaktifkan kembali akses IaaS pengguna
     */
    public function toggleCredentialStatus($credentialId)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $credential = Credential::findOrFail($credentialId);
        $newStatus = $credential->status_kunci === 'Aktif' ? 'Dicabut' : 'Aktif';
        
        $credential->update(['status_kunci' => $newStatus]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Perubahan Status Kredensial',
            'description' => 'Administrator mengubah status kunci ID ' . $credential->id . ' menjadi ' . $newStatus
        ]);

        return response()->json(['message' => "Pembaruan status berhasil. Status kunci akses sekarang: $newStatus"]);
    }

    /**
     * ALUR 4: API untuk mengambil riwayat kontrak sewa milik pengguna yang sedang login.
     */
    public function getUserSubscriptions()
    {
        $subscriptions = UserSubscription::with(['plan', 'payment', 'resource', 'credential'])
            ->where('user_id', Auth::id())
            ->get();

        return response()->json([
            'message' => 'Berhasil mengambil data riwayat penyewaan.',
            'data' => $subscriptions
        ], 200);
    }

    /**
     * ALUR 5: API untuk mengambil log aktivitas penyewaan sesuai jobdesk.
     */
    public function getUserActivityLogs()
    {
        $query = ActivityLog::with('user:id,name,email')->latest();

        if (Auth::user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        return response()->json([
            'message' => 'Berhasil mengambil log aktivitas.',
            'data' => $query->get()
        ], 200);
    }
}