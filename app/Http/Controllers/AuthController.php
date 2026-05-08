<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. REGISTER (Strictly for System Setup Only)
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,teacher,student',
        ]);

        if ($validated['role'] === 'admin') {
            if (User::where('role', 'admin')->exists()) {
                return response()->json(['message' => 'System already set up. Admin account exists.'], 403);
            }
        } else {
            return response()->json(['message' => 'Public registration is closed. Contact the Administrator.'], 403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        // JWT FIX: Type hint the guard so IDE knows 'login' exists
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        return response()->json([
            'message' => 'System Admin registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    // 2. LOGIN
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        // JWT FIX: Use variable to attempt login
        if (! $token = $guard->attempt($credentials)) {
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        /** @var \App\Models\User $user */
        $user = $guard->user();

        if (!$user->is_active) {
            $guard->logout(); // Invalidate token immediately
            return response()->json(['message' => 'Your account has been deactivated by the Admin.'], 403);
        }

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    // 3. REFRESH TOKEN
    public function refresh()
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return response()->json([
            'message' => 'Token refreshed',
            'access_token' => $guard->refresh(),
            'token_type' => 'Bearer',
            'user' => $guard->user()
        ]);
    }

    // 4. LOGOUT
    public function logout()
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // 5. PROFILE
    public function userProfile()
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        return response()->json($guard->user());
    }

    // 6. UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        /** @var \App\Models\User $user */
        $user = $guard->user(); 
        
        if (!$user) {
            return response()->json(['message' => 'User not found or token expired'], 401);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6',
        ]);

        if ($request->has('name')) $user->name = $validated['name'];
        if ($request->has('password')) $user->password = Hash::make($validated['password']);

        $user->save();
        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }
}