<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CredentialPageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $activeSubscription = DB::table('user_subscriptions as us')
            ->join('subscription_plans as sp', 'sp.id', '=', 'us.plan_id')
            ->where('us.user_id', $user->id)
            ->where('us.status', 'active')
            ->select(
                'us.id',
                'us.status',
                'sp.name as plan_name',
                'sp.storage_quota_gb',
                'sp.max_buckets'
            )
            ->orderByDesc('us.created_at')
            ->first();

        $credential = null;
        $bucket = null;
        $secretAccessKey = null;

        if ($activeSubscription) {
            $credential = DB::table('credentials')
                ->where('subscription_id', $activeSubscription->id)
                ->first();

            $resource = DB::table('resources')
                ->where('subscription_id', $activeSubscription->id)
                ->first();

            if ($resource) {
                $bucket = DB::table('buckets')
                    ->where('resource_id', $resource->id)
                    ->first();
            }

            if ($credential && $request->boolean('show_secret')) {
                try {
                    $secretAccessKey = decrypt($credential->secret_access_key);
                } catch (\Throwable $e) {
                    $secretAccessKey = 'Secret key gagal didekripsi.';
                }
            }
        }

        $endpoint = config('services.ministack.endpoint', env('MINISTACK_ENDPOINT', 'http://localhost:4566'));

        return view('credentials.index', compact(
            'activeSubscription',
            'credential',
            'bucket',
            'secretAccessKey',
            'endpoint'
        ));
    }
}