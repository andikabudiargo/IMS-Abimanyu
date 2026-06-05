<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        // Berikan pengecualian middleware auth untuk login dan register
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'username' => 'required|string|alpha_dash|between:3,50|unique:users',
            'email' => 'string|email|max:100|unique:users',
            'password' => 'required|string|min:5|confirmed',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username, // Simpan username ke database
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'User berhasil didaftarkan',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('username', 'password');

        // Tambahkan guard('api') sebelum memanggil attempt
        if (! $token = auth()->guard('api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Incorrect username or password'
            ], 401);
        }

        // if (! $token = auth()->attempt($credentials)) {
        //     return response()->json(['error' => 'Username atau password salah'], 401);
        // }

        return $this->createNewToken($token);
    }

    public function profile()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Berhasil logout']);
    }

    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'access_token' => $token,
            'token_type' => 'bearer',
            // Mengambil nilai TTL langsung dari config jwt (bawaannya adalah 60 menit)
            'expires_in' => config('jwt.ttl') * 60, 
            'user' => [
                'username' => auth('api')->user()->username,
                'name'     => auth('api')->user()->name
            ]
        ]);
    }

}