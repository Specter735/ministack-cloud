<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return view('admin.payments.index', compact('payments', 'status', 'pendingCount'));
    }

    /**
     * ACC satu nota pembayaran: aktifkan kontrak sewa & alokasikan infrastruktur IaaS.
     */
    public function verify(Payment $payment): RedirectResponse
    {
        try {
            $this->paymentVerificationService->verify($payment->id, Auth::id());

            return redirect()
                ->route('admin.payments.index')
                ->with('success', "Pembayaran #{$payment->id} berhasil diverifikasi. Infrastruktur IaaS telah dialokasikan.");
        } catch (RuntimeException $e) {
            // Contoh: nota sudah Lunas sebelumnya
            return redirect()
                ->route('admin.payments.index')
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Gagal memverifikasi pembayaran: ' . $e->getMessage());
        }
    }
}
