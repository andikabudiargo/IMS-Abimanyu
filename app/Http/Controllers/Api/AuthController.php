<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;   // ← TAMBAH INI

class AuthController extends Controller
{
    public function login(Request $request)
{
    $request->validate([
        'username'    => 'required|string',
        'password'    => 'required|string',
        'device_name' => 'nullable|string',
    ]);

    $user = User::where('username', $request->username)->first();

   if (! $user || ! Hash::check($request->password, $user->password)) {
    return response()->json(['message' => 'Username atau password salah'], 401);
}

// ← TAMBAH: cek akun aktif
if ((string) $user->status !== '1') {   // sesuaikan kolom & nilai "aktif"
    return response()->json([
        'message' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
    ], 403);
}


    $deviceName = $request->device_name ?: 'Perangkat tidak dikenal';

    // single-device: cabut semua sesi lama
    $user->tokens()->delete();

    $user->forceFill([
    'last_mobile_login_at'     => now(),
    'last_mobile_login_device' => $deviceName,
])->save();

    $token = $user->createToken($deviceName)->plainTextToken;
    $dept  = DB::table('user_dept')->where('username', $user->username)->value('dept');
    $roles = $user->getRoleNames();

    return response()->json([
        'message' => 'Login berhasil',
        'user'  => [
            'id'       => $user->id,
            'username' => $user->username,
            'name'     => $user->name,
            'email'    => $user->email,
            'dept'     => $dept,
            'roles'    => $roles,
        ],
        'token' => $token,
    ]);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

   public function me(Request $request)
{
    $user  = $request->user();
    $dept  = DB::table('user_dept')->where('username', $user->username)->value('dept');
    $roles = $user->getRoleNames();

    return response()->json([
        'id'       => $user->id,
        'username' => $user->username,
        'name'     => $user->name,
        'email'    => $user->email,
        'dept'     => $dept,
        'roles'    => $roles,
    ]);
}
}