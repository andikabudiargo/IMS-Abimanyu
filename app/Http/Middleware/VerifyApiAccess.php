<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use DB;

class VerifyApiAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    
    public function handle($request, Closure $next)
    {
        /*
            User yang bisa hit API adalah user yang status nya whitelisted (di tabel users) dan ip address sudah di daftar di tabel whitelist_ip dengan is_active = true
        */

        // 1. Cek Whitelist IP Address
        $listIp = DB::table('whitelist_ip')->where('is_active', true)->pluck('ip_address')->toArray();
    
        $allowedIps = array_merge($listIp, ['127.0.0.1', '::1']);

        // $allowedIps = [
        //     '127.0.0.1',      // Localhost IPv4
        //     '::1',            // Localhost IPv6
        //     // '192.168.1.50',   // Contoh IP Server Klien Mandiri
        //     // Tambahkan IP publik atau IP server klien Anda di sini
        // ];

        if (!in_array($request->ip(), $allowedIps)) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => 'Your IP Address (' . $request->ip() . ') is not whitelisted.',
                'error_code' => 403
            ], 403);
        }

        // 2. Cek Whitelist Status User (Hanya jika user sudah terautentikasi JWT)
        $user = Auth::guard('api')->user();
        
        if ($user && !$user->is_whitelisted) {
            return response()->json([
                'error' => 'Account Blocked',
                'message' => 'Your user account is not authorized to access this API.',
                'error_code' => 403
            ], 403);
        }

        // ------------------------------------------------------------------
        // 3. API RATE LIMITING KUSTOM
        // ------------------------------------------------------------------
        // Membuat kunci unik per baris request berdasarkan ID user atau IP jika belum login\

        $limiter = app(\Illuminate\Cache\RateLimiter::class);
        $executedKey = $user 
            ? 'user_limit:' . $user->id 
            : 'ip_limit:' . Str::slug($request->ip());

        // Tentukan batas maksimal request (contoh: 30 request) dan waktu reset (1 menit / 60 detik)
        $maxAttempts = 30; 
        $decayMinutes = 1; 

        if (app(\Illuminate\Cache\RateLimiter::class)->tooManyAttempts($executedKey, $maxAttempts)) {
            $secondsLeft = app(\Illuminate\Cache\RateLimiter::class)->availableIn($executedKey);
            
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again in ' . $secondsLeft . ' seconds.',
                'retry_after_seconds' => $secondsLeft,
                'error_code' => 429
            ], 429);
        }

        // Hitung baris request berjalan jika belum melewati batas
        $limiter->hit($executedKey, $decayMinutes * 60);

        // Execute the next middleware / controller action (Only execute ONCE)
        $response = $next($request);
        
        // Calculate remaining hits using Laravel 7 native retriesLeft helper
        $remainingAttempts = $limiter->retriesLeft($executedKey, $maxAttempts);

        // Sisipkan informasi sisa limit ke HTTP Headers
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remainingAttempts);

        return $next($request);
    }
}