<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Bucket;
use App\Models\Credential;
use App\Models\Payment;
use App\Models\Resource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentVerificationService
{
    public function __construct(protected MiniStackService $miniStackService)
    {
    }

    /**
     * Verifikasi satu nota pembayaran: aktifkan kontrak sewa & alokasikan
     * infrastruktur IaaS (Resource, Bucket, Credential) sekaligus.
     *
     * Dipakai bersama oleh:
     * - IaasTransactionController::verifyPayment() (endpoint REST API / Postman)
     * - Admin\AdminPaymentController::verify() (halaman admin web)
     *
     * @throws ModelNotFoundException jika nota pembayaran tidak ditemukan
     * @throws RuntimeException       jika nota pembayaran sudah Lunas sebelumnya
     */
    public function verify(int $paymentId, int $verifiedByUserId): Payment
    {
        // MEMUAT RELASI USER SEKALIGUS UNTUK MENCEGAH ATTEMPT TO READ PROPERTY NAME ON NULL
        $payment = Payment::with(['subscription.plan', 'subscription.user'])->findOrFail($paymentId);

        if ($payment->status_bayar === 'Lunas') {
            throw new RuntimeException('Nota pembayaran ini telah diverifikasi sebelumnya.');
        }

        DB::transaction(function () use ($payment, $verifiedByUserId) {
            // 1. Mengubah status pembayaran menjadi Lunas dan mengaktifkan kontrak sewa
            $payment->update(['status_bayar' => 'Lunas']);

            $subscription = $payment->subscription;
            $subscription->update(['status' => 'active']);

            $plan = $subscription->plan;

            // 2. Mengalokasikan sumber daya (Resource)
            $resource = Resource::create([
                'subscription_id' => $subscription->id,
                'kapasitas_storage' => $plan->storage_quota_gb,
            ]);

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
                'used_storage_mb' => 0,
            ]);

            // 5. Menerbitkan kunci akses (Credentials)
            Credential::create([
                'subscription_id' => $subscription->id,
                'ministack_account_id' => $miniStackData['account_id'],
                'access_key_id' => $miniStackData['access_key_id'],
                'secret_access_key' => encrypt($miniStackData['secret_access_key']),
                'bucket_name' => $bucket->bucket_name,
                'status_kunci' => 'Aktif',
            ]);

            // 6. Mencatat tindakan verifikasi administrator ke dalam log
            ActivityLog::create([
                'user_id' => $verifiedByUserId,
                'action' => 'Verifikasi Pembayaran',
                'description' => 'Administrator telah memverifikasi pembayaran ID ' . $payment->id . ' dan infrastruktur IaaS telah dialokasikan.',
            ]);
        });

        return $payment->fresh(['subscription.plan', 'subscription.user']);
    }
}
