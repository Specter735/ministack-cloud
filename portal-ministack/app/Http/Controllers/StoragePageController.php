<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoragePageController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $plans = DB::table('subscription_plans')
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $subscriptions = DB::table('user_subscriptions as us')
            ->join('subscription_plans as sp', 'sp.id', '=', 'us.plan_id')
            ->leftJoin('payments as p', 'p.subscription_id', '=', 'us.id')
            ->where('us.user_id', $user->id)
            ->select(
                'us.id',
                'us.plan_id',
                'us.status',
                'us.subscribed_at',
                'us.expires_at',
                'sp.name as plan_name',
                'sp.description as plan_description',
                'sp.price',
                'sp.storage_quota_gb',
                'sp.max_buckets',
                'p.metode_bayar',
                'p.status_bayar'
            )
            ->orderByDesc('us.created_at')
            ->get();

        $activeSubscription = $subscriptions->firstWhere('status', 'active');

        $resource = null;
        $bucket = null;
        $credential = null;

        $usedStorageMb = 0;
        $totalStorageMb = 0;
        $remainingStorageMb = 0;
        $usedPercent = 0;

        if ($activeSubscription) {
            $resource = DB::table('resources')
                ->where('subscription_id', $activeSubscription->id)
                ->first();

            if ($resource) {
                $bucket = DB::table('buckets')
                    ->where('resource_id', $resource->id)
                    ->first();
            }

            $credential = DB::table('credentials')
                ->where('subscription_id', $activeSubscription->id)
                ->first();

            $usedStorageMb = $bucket->used_storage_mb ?? 0;
            $totalStorageMb = (int) $activeSubscription->storage_quota_gb * 1024;
            $remainingStorageMb = max($totalStorageMb - $usedStorageMb, 0);
            $usedPercent = $totalStorageMb > 0
                ? round(($usedStorageMb / $totalStorageMb) * 100, 2)
                : 0;
        }

        // Cek apakah user boleh checkout — dipakai oleh view untuk menyembunyikan form
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

        return view('storage.index', compact(
            'plans',
            'subscriptions',
            'activeSubscription',
            'resource',
            'bucket',
            'credential',
            'usedStorageMb',
            'totalStorageMb',
            'remainingStorageMb',
            'usedPercent',
            'blockCheckout',
            'blockReason'
        ));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id'      => ['required', 'exists:subscription_plans,id'],
            'metode_bayar' => ['required', 'string', 'max:100'],
        ]);

        $user = Auth::user();

        // ── GUARD: tolak checkout baru jika masih ada pengajuan aktif/pending ──
        $existingStatus = DB::table('user_subscriptions')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->value('status'); // ambil satu nilai, lebih ringan dari first()

        if ($existingStatus === 'active') {
            return redirect()
                ->route('storage.index')
                ->with('error', 'Kamu sudah memiliki paket storage yang aktif. Tidak dapat mengajukan paket baru sebelum paket saat ini berakhir.');
        }

        if ($existingStatus === 'pending') {
            return redirect()
                ->route('storage.index')
                ->with('error', 'Kamu masih memiliki pengajuan yang sedang menunggu verifikasi admin. Silakan tunggu hingga diproses sebelum mengajukan paket baru.');
        }
        // ── END GUARD ──

        $plan = DB::table('subscription_plans')
            ->where('id', $request->plan_id)
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            return redirect()
                ->route('storage.index')
                ->with('error', 'Paket layanan tidak tersedia.');
        }

        DB::beginTransaction();

        try {
            $subscriptionId = DB::table('user_subscriptions')->insertGetId([
                'user_id'      => $user->id,
                'plan_id'      => $plan->id,
                'subscribed_at' => now(),
                'expires_at'   => now()->addMonth(),
                'status'       => 'pending',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('payments')->insert([
                'subscription_id' => $subscriptionId,
                'metode_bayar'    => $request->metode_bayar,
                'status_bayar'    => 'Pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('activity_logs')->insert([
                'user_id'     => $user->id,
                'action'      => 'Checkout Paket',
                'description' => 'Pengguna mengajukan penyewaan paket ' . $plan->name,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('storage.index')
                ->with('success', 'Pengajuan sewa berhasil dibuat. Silakan tunggu verifikasi pembayaran oleh admin.');

        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('storage.index')
                ->with('error', 'Pengajuan sewa gagal: ' . $e->getMessage());
        }
    }
}