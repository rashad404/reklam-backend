<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function walletCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'code_verifier' => 'required|string',
            'redirect_uri' => 'required|string',
        ]);

        $walletApiUrl = config('services.wallet.api_url', env('WALLET_API_URL'));
        $clientId = config('services.wallet.client_id', env('WALLET_CLIENT_ID'));
        $clientSecret = config('services.wallet.client_secret', env('WALLET_CLIENT_SECRET'));

        // Exchange code for tokens with Kimlik.az
        $tokenResponse = Http::post("{$walletApiUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $request->code,
            'code_verifier' => $request->code_verifier,
            'redirect_uri' => $request->redirect_uri,
        ]);

        if (!$tokenResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to exchange authorization code',
            ], 400);
        }

        $tokens = $tokenResponse->json();
        $accessToken = $tokens['access_token'] ?? null;

        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'No access token received',
            ], 400);
        }

        // Get user profile from Kimlik.az
        $profileResponse = Http::withToken($accessToken)->get("{$walletApiUrl}/user");

        if (!$profileResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get user profile',
            ], 400);
        }

        $profile = $profileResponse->json('data', $profileResponse->json());

        // Find or create user
        $user = User::where('wallet_id', $profile['id'])->first();

        if (!$user) {
            $user = User::where('email', $profile['email'] ?? '')->first();
        }

        if ($user) {
            $user->update([
                'wallet_id' => $profile['id'],
                'wallet_access_token' => $accessToken,
                'wallet_refresh_token' => $tokens['refresh_token'] ?? null,
                'name' => $profile['name'] ?? $user->name,
                'phone' => $profile['phone'] ?? $user->phone,
            ]);
        } else {
            $user = User::create([
                'name' => $profile['name'] ?? 'User',
                'email' => $profile['email'] ?? $profile['id'] . '@wallet.user',
                'phone' => $profile['phone'] ?? null,
                'wallet_id' => $profile['id'],
                'wallet_access_token' => $accessToken,
                'wallet_refresh_token' => $tokens['refresh_token'] ?? null,
            ]);
        }

        $token = $user->createToken('wallet-auth')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load(['advertiser', 'publisher']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }
}
