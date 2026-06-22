<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UserSubscription;
use App\Models\Bucket;
use App\Models\SubscriptionPlan;
use App\Services\MiniStackService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // --- Sinkronkan pemakaian storage asli dari MiniStack ---
        // $this->syncStorageUsage($user);

        // Mengecek apakah pengguna memiliki kontrak langganan yang berstatus aktif
        $activeSub = UserSubscription::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();

        $paketName = 'Belum Berlangganan';
        $totalStorageGb = 0;
        $storageUsedMb = 0;
        $bucketsCount = 0;

        // Jika langganan aktif ditemukan, sistem mengambil parameter kuota dan kalkulasi bucket
        if ($activeSub) {
            $paketName = $activeSub->plan->name;
            $totalStorageGb = $activeSub->plan->storage_quota_gb;

            // Menghitung total penggunaan storage (MB) secara relasional
            $storageUsedMb = Bucket::join('resources', 'buckets.resource_id', '=', 'resources.id')
                ->where('resources.subscription_id', $activeSub->id)
                ->sum('buckets.used_storage_mb');

            // Menghitung jumlah bucket yang dialokasikan
            $bucketsCount = Bucket::join('resources', 'buckets.resource_id', '=', 'resources.id')
                ->where('resources.subscription_id', $activeSub->id)
                ->count('buckets.id');
        }

        $realData = [
            'storage_used'  => round($storageUsedMb, 2),  // Menampilkan MB
            'storage_total' => $totalStorageGb * 1024,    // Konversi GB ke MB
            'package'       => $paketName,
            'buckets_count' => $bucketsCount,
        ];

        // Daftar paket aktif untuk form "Beli Paket IaaS" (sumber sama dengan halaman Storage)
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        // Guard checkout — konsisten dengan StoragePageController
        $existingStatus = DB::table('user_subscriptions')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->value('status');

        $blockCheckout = !is_null($existingStatus);
        $blockReason   = match ($existingStatus) {
            'active'  => 'Kamu sudah memiliki paket storage yang aktif.',
            'pending' => 'Kamu masih memiliki pengajuan yang menunggu verifikasi admin.',
            default   => null,
        };

        // Mengirimkan objek data ke tampilan antarmuka (View)
        return view('dashboard', compact('user', 'realData', 'plans', 'blockCheckout', 'blockReason'));
    }

    /**
     * Menarik data pemakaian storage secara langsung dari MiniStack, lalu memperbarui basis data lokal.
     */
    protected function syncStorageUsage($user): void
    {
        $activeSub = UserSubscription::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();

        if (!$activeSub) {
            return; // Pengguna tidak memiliki langganan aktif, proses dihentikan.
        }

        // Mengambil kredensial dan sumber daya melalui relasi kontrak sewa
        $credential = $activeSub->credential;
        $resource = $activeSub->resource;

        if (!$credential || !$resource) {
            return; // Infrastruktur IaaS belum teralokasi secara utuh.
        }

        try {
            $secretKey = Crypt::decryptString($credential->secret_access_key);
            $ministack = new MiniStackService();

            // Mengambil daftar bucket yang terikat dengan ID resource
            $buckets = Bucket::where('resource_id', $resource->id)->get();

            foreach ($buckets as $bucket) {
                $usageMb = $ministack->getBucketUsageMb(
                    $credential->ministack_account_id,
                    $credential->access_key_id,
                    $secretKey,
                    $bucket->bucket_name
                );

                $bucket->update(['used_storage_mb' => $usageMb]);
            }
        } catch (\Exception $e) {
            // Sistem merekam galat ke dalam file log jika layanan MiniStack sedang terganggu
            Log::warning('Kegagalan sinkronisasi storage MiniStack: ' . $e->getMessage());
        }
    }
}