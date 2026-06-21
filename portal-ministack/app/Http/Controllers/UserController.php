<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    /**
     * Memastikan hanya Admin yang dapat mengakses endpoint CRUD ini.
     * Menggunakan standar implementasi HasMiddleware pada Laravel 11.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (Auth::user()->role !== 'admin') {
                    return response()->json(['error' => 'Akses ditolak. Otorisasi Administrator diperlukan.'], 403);
                }
                return $next($request);
            }),
        ];
    }

    /**
     * READ: Menampilkan daftar seluruh pengguna.
     */
    public function index()
    {
        $users = User::all();
        return response()->json([
            'message' => 'Berhasil mengambil daftar pengguna.',
            'data' => $users
        ], 200);
    }

    /**
     * CREATE: Menambahkan pengguna baru secara manual oleh Admin.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Create User',
            'description' => 'Administrator menambahkan pengguna baru dengan ID ' . $user->id
        ]);

        return response()->json([
            'message' => 'Pengguna baru berhasil ditambahkan.',
            'data' => $user
        ], 201);
    }

    /**
     * READ DETAIL: Menampilkan spesifikasi data satu pengguna.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'message' => 'Detail pengguna ditemukan.',
            'data' => $user
        ], 200);
    }

    /**
     * UPDATE: Mengubah data pengguna yang sudah ada.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|required|string|min:8',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'user'])],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Update User',
            'description' => 'Administrator mengubah data pengguna dengan ID ' . $user->id
        ]);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'data' => $user
        ], 200);
    }

    /**
     * DELETE: Menghapus pengguna dari sistem secara permanen.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Mencegah Administrator menghapus akunnya sendiri
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'Tindakan ilegal: Anda tidak dapat menghapus akun Anda sendiri.'], 400);
        }

        $user->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Delete User',
            'description' => 'Administrator menghapus pengguna dengan ID ' . $id
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil dihapus dari sistem.'
        ], 200);
    }
}