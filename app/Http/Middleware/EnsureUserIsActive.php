<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && (string) $user->status !== '1') {   // sesuaikan kolom
            // cabut semua token supaya device lain juga ikut terputus
            $user->tokens()->delete();

            return response()->json([
                'status'  => 'error',
                'code'    => 403,
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
                'error'   => 'ACCOUNT_DISABLED',
            ], 403);
        }

        return $next($request);
    }
}