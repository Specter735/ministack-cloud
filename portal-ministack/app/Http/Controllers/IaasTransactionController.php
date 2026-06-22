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
            // 1. Mengubah status pembayaran menjadi Lunas dan mengaktifkan kontrak sewa
            $payment->status_bayar = 'Lunas';
            $payment->save();

            $subscription = $payment->subscription;
            $subscription->status = 'active';
            $subscription->save(); // Aktivasi status kontrak sewa

            $plan = $subscription->plan;

            // 2. Mengalokasikan sumber daya (Resource)
            $resource = Resource::create([
                'subscription_id' => $subscription->id,
                'kapasitas_storage' => $plan->storage_quota_gb
            ]);

            // Mengambil entitas User yang kini sudah dijamin aman ketersediaannya di memori
            $user = $subscription->user; 
            
            // Asumsi Account ID AWS fiktif karena ini LocalStack
            $dummyAccountId = '000000000000'; 

            // 3. Memanggil fungsi provisionUser() dari rekan tim
            $miniStackData = $this->miniStackService->provisionUser(
                $dummyAccountId, 
                str_replace(' ', '', strtolower($user->name))
            );

            // 4. Mencatat entitas Bucket
            $bucket = Bucket::create([
                'resource_id' => $resource->id,
                'bucket_name' => $miniStackData['bucket_name'],
                'ministack_bucket_id' => null,
                'used_storage_mb' => 0
            ]);

            // 5. Menerbitkan kunci akses (Credentials)
            Credential::create([
                'subscription_id' => $subscription->id,
                'ministack_account_id' => $miniStackData['account_id'],
                'access_key_id' => $miniStackData['access_key_id'],
                'secret_access_key' => encrypt($miniStackData['secret_access_key']),
                'bucket_name' => $bucket->bucket_name,
                'status_kunci' => 'Aktif'
            ]);

            // 6. Mencatat tindakan verifikasi administrator ke dalam log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Verifikasi Pembayaran',
                'description' => 'Administrator telah memverifikasi pembayaran ID ' . $payment->id . ' dan infrastruktur IaaS telah dialokasikan.'
            ]);

            DB::commit();
            $payment->refresh();
            $subscription->refresh();

            return response()->json([
                'message' => 'Verifikasi berhasil. Infrastruktur IaaS siap digunakan oleh pelanggan.',
                'payment_id' => $payment->id,
                'status_bayar' => $payment->status_bayar,
                'subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
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