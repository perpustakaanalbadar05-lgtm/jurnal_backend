<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'institution'      => 'nullable|string|max:255',
            'current_password' => 'nullable|string',
            'password'         => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($validated['password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Password saat ini tidak valid.'], 422);
            }
            $user->password = Hash::make($validated['password']);
        }

        $user->name        = $validated['name'];
        $user->email       = $validated['email'];
        $user->institution = $validated['institution'];
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user'    => $user
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path            = $request->file('avatar')->store('avatars', 'public');
        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'message'    => 'Avatar berhasil diperbarui.',
            'avatar_url' => asset('storage/' . $path),
            'user'       => $user
        ]);
    }
}
