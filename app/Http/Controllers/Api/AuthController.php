<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akun Anda telah dinonaktifkan.'], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLog::log('login', "User {$user->name} logged in", $user);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        ActivityLog::log('logout', "User {$request->user()->name} logged out", $request->user());
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
