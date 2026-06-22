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
            'plan_id'      => 'required|exists:subscription_plans,id',
            'metode_bayar' => 'required|string'
        ]);

        // ── GUARD: tolak checkout baru jika masih ada pengajuan aktif/pending ──
        $existingStatus = UserSubscription::where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'active'])
            ->value('status');

        if ($existingStatus === 'active') {
            return response()->json([
                'message' => 'Kamu sudah memiliki paket storage yang aktif. Tidak dapat mengajukan paket baru sebelum paket saat ini berakhir.'
            ], 422);
        }

        if ($existingStatus === 'pending') {
            return response()->json([
                'message' => 'Kamu masih memiliki pengajuan yang sedang menunggu verifikasi admin. Silakan tunggu hingga diproses sebelum mengajukan paket baru.'
            ], 422);
        }
        // ── END GUARD ──

        // Memulai transaksi basis data untuk menjamin integritas relasional
        DB::beginTransaction();
        try {
            // 1. Mencatat kontrak sewa baru dengan status 'pending' sesuai alur pembayaran
            $subscription = UserSubscription::create([
                'user_id'       => Auth::id(),
                'plan_id'       => $request->plan_id,
                'subscribed_at' => now(),
                'expires_at'    => now()->addMonth(),
                'status'        => 'pending'
            ]);

            // 2. Membuat nota pembayaran yang harus dilunasi
            $payment = Payment::create([
                'subscription_id' => $subscription->id,
                'metode_bayar'    => $request->metode_bayar,
                'status_bayar'    => 'Pending'
            ]);

            // 3. Mencatat aktivitas ke dalam log
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'Checkout Paket',
                'description' => 'Pengguna mengajukan penyewaan paket ID ' . $request->plan_id
            ]);

            DB::commit();
            return response()->json([
                'message'    => 'Proses checkout berhasil. Silakan selesaikan pembayaran.',
                'payment_id' => $payment->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Kegagalan sistem saat memproses checkout: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ALUR 1B: Pengguna mengupgrade paket — subscription lama di-expire, buat yang baru (pending).
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id'      => 'required|exists:subscription_plans,id',
            'metode_bayar' => 'required|string',
        ]);

        // Harus ada subscription active untuk bisa upgrade
        $activeSubscription = UserSubscription::where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        if (!$activeSubscription) {
            return response()->json([
                'message' => 'Tidak ada paket aktif yang dapat diupgrade. Gunakan fitur Checkout untuk berlangganan pertama kali.'
            ], 422);
        }

        // Tidak boleh upgrade ke paket yang sama
        if ((int) $activeSubscription->plan_id === (int) $request->plan_id) {
            return response()->json([
                'message' => 'Kamu sudah menggunakan paket ini. Pilih paket yang berbeda untuk melakukan upgrade.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Matikan subscription & credential lama
            $activeSubscription->update(['status' => 'expired']);

            // Cabut kredensial lama otomatis agar tidak ada akses ganda
            \App\Models\Credential::where('subscription_id', $activeSubscription->id)
                ->update(['status_kunci' => 'Dicabut']);

            // 2. Buat subscription baru dengan status pending
            $newSubscription = UserSubscription::create([
                'user_id'       => Auth::id(),
                'plan_id'       => $request->plan_id,
                'subscribed_at' => now(),
                'expires_at'    => now()->addMonth(),
                'status'        => 'pending',
            ]);

            // 3. Buat nota pembayaran baru
            $payment = Payment::create([
                'subscription_id' => $newSubscription->id,
                'metode_bayar'    => $request->metode_bayar,
                'status_bayar'    => 'Pending',
            ]);

            // 4. Catat ke log
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'Upgrade Paket',
                'description' => 'Pengguna mengupgrade dari paket ID ' . $activeSubscription->plan_id
                               . ' ke paket ID ' . $request->plan_id . '.',
            ]);

            DB::commit();

            return response()->json([
                'message'    => 'Permintaan upgrade berhasil diajukan. Paket lama dinonaktifkan, silakan tunggu verifikasi admin.',
                'payment_id' => $payment->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Kegagalan sistem saat memproses upgrade: ' . $e->getMessage()
            ], 500);
        }
    }


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

        $credential = Credential::with('subscription.user')->findOrFail($credentialId);
        $newStatus  = $credential->status_kunci === 'Aktif' ? 'Dicabut' : 'Aktif';

        $credential->update(['status_kunci' => $newStatus]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Perubahan Status Kredensial',
            'description' => 'Administrator mengubah status kunci ID ' . $credential->id
                           . ' atas nama ' . ($credential->subscription->user->name ?? 'N/A')
                           . ' menjadi ' . $newStatus . '.',
        ]);

        return response()->json([
            'message'    => "Pembaruan status berhasil. Status kunci akses sekarang: {$newStatus}",
            'new_status' => $newStatus,
        ]);
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