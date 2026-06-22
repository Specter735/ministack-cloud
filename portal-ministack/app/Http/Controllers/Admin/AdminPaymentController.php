<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Services\PaymentVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AdminPaymentController extends Controller
{
    public function __construct(protected PaymentVerificationService $paymentVerificationService)
    {
    }

    /**
     * Tampilkan daftar nota pembayaran. Default: status Pending saja.
     * Filter status lain bisa diakses lewat ?status=Lunas atau ?status=all
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'Pending');

        $payments = Payment::with(['subscription.plan', 'subscription.user'])
            ->when($status !== 'all', fn ($query) => $query->where('status_bayar', $status))
            ->latest()
            ->get();

        $pendingCount = Payment::where('status_bayar', 'Pending')->count();
        $lunasCount = Payment::where('status_bayar', 'Lunas')->count();
        $ditolakCount = Payment::where('status_bayar', 'Ditolak')->count();

        return view('admin.payments.index', compact(
            'payments',
            'status',
            'pendingCount',
            'lunasCount',
            'ditolakCount'
        ));

        
    }

    /**
     * ACC satu nota pembayaran: aktifkan kontrak sewa & alokasikan infrastruktur IaaS.
     */
    public function verify(Payment $payment): RedirectResponse
    {
        if ($payment->status_bayar !== 'Pending') {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', "Pembayaran #{$payment->id} tidak dapat diverifikasi karena statusnya sudah {$payment->status_bayar}.");
        }

        try {
            $this->paymentVerificationService->verify($payment->id, Auth::id());

            return redirect()
                ->route('admin.payments.index')
                ->with('success', "Pembayaran #{$payment->id} berhasil diverifikasi. Infrastruktur IaaS telah dialokasikan.");
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Gagal memverifikasi pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Tolak satu nota pembayaran: batalkan kontrak sewa, user bisa mengajukan ulang.
     */
    public function reject(Payment $payment): RedirectResponse
    {
        if ($payment->status_bayar !== 'Pending') {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', "Pembayaran #{$payment->id} tidak dapat ditolak karena statusnya sudah {$payment->status_bayar}.");
        }

        $payment->update(['status_bayar' => 'Ditolak']);

        if ($payment->subscription) {
            $payment->subscription->update(['status' => 'cancelled']);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Tolak Pembayaran',
            'description' => 'Administrator menolak pembayaran ID ' . $payment->id . '.',
        ]);

        return redirect()
            ->route('admin.payments.index')
            ->with('success', "Pembayaran #{$payment->id} berhasil ditolak.");
    }
}