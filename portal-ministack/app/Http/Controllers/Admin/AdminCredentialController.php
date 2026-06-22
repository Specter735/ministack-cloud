<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Credential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminCredentialController extends Controller
{
    /**
     * Tampilkan semua kredensial. Default: semua status.
     * Filter: ?status=Aktif atau ?status=Dicabut
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $credentials = Credential::with(['subscription.user', 'subscription.plan'])
            ->when($status !== 'all', fn ($q) => $q->where('status_kunci', $status))
            ->latest()
            ->get();

        $aktifCount   = Credential::where('status_kunci', 'Aktif')->count();
        $dicabutCount = Credential::where('status_kunci', 'Dicabut')->count();

        return view('admin.credentials.index', compact(
            'credentials', 'status', 'aktifCount', 'dicabutCount'
        ));
    }

    /**
     * Toggle status kunci: Aktif → Dicabut atau sebaliknya.
     */
    public function toggle(Credential $credential): RedirectResponse
    {
        $newStatus = $credential->status_kunci === 'Aktif' ? 'Dicabut' : 'Aktif';
        $credential->update(['status_kunci' => $newStatus]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Perubahan Status Kredensial',
            'description' => 'Administrator mengubah status kunci ID ' . $credential->id
                           . ' atas nama ' . ($credential->subscription->user->name ?? 'N/A')
                           . ' menjadi ' . $newStatus . '.',
        ]);

        $label = $credential->subscription->user->name ?? "ID #{$credential->id}";

        return redirect()
            ->route('admin.credentials.index')
            ->with('success', "Status kredensial milik {$label} berhasil diubah menjadi {$newStatus}.");
    }
}
