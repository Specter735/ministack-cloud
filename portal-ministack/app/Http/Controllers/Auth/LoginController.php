<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Tampilkan form login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses login (Hibrida: Sesi Web + Token API)
     */
    public function login(Request $request)
    {
        // 1. Validasi input
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Coba autentikasi Sesi Web
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Bersihkan token lama & terbitkan yang baru
            $user->tokens()->delete();
            $token = $user->createToken('ChromaStackToken')->plainTextToken;

            // 3. PAKSA KEMBALIKAN JSON JIKA PERMINTAAN DARI JAVASCRIPT
            if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'token'   => $token,
                ]);
            }

            // Fallback jika login dilakukan tanpa JavaScript (langsung dari browser)
            return redirect()->intended(route('dashboard'));
        }

        // 4. Jika autentikasi gagal
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.'
            ], 401);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    /**
     * Proses logout
     */
    public function logout(Request $request)
    {
        // Hapus token API pengguna menggunakan objek request agar dikenali oleh editor
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}