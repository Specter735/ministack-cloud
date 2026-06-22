<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request (Hibrida: Sesi Web + Token API).
     */
    public function store(LoginRequest $request)
    {
        // Validasi kredensial + rate limiting bawaan LoginRequest tetap jalan
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        $user->tokens()->delete();

        $token = $user->createToken('ChromaStackToken')->plainTextToken;

        if (
            $request->expectsJson() ||
            $request->ajax() ||
            $request->header('X-Requested-With') === 'XMLHttpRequest'
        ) {
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'token' => $token,
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}