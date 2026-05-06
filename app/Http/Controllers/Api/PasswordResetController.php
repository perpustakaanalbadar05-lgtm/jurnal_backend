<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // In a real app, you would send an actual email here.
        // For now, we'll return the token in the response so you can test it,
        // but I'll add the logic for the Mail as well.
        
        $resetUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        Mail::send('emails.forgot-password', ['url' => $resetUrl], function($message) use($request){
            $message->to($request->email);
            $message->subject('Reset Password Notification');
        });

        return response()->json([
            'message' => 'Kami telah mengirimkan instruksi reset password ke email Anda.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where([
                'email' => $request->email,
                'token' => $request->token,
            ])
            ->first();

        if (!$reset || Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Token tidak valid atau sudah kadaluwarsa.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();

        return response()->json(['message' => 'Password berhasil diubah. Silakan login kembali.']);
    }
}
