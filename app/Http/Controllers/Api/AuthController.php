<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        try {
            $walletApiUrl = env('WALLET_API_URL', 'https://api.kimlik.az/api');
            $clientId = env('WALLET_CLIENT_ID');
            $clientSecret = env('WALLET_CLIENT_SECRET');

            // Exchange authorization code for tokens
            $tokenResponse = Http::post("{$walletApiUrl}/oauth/token", [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $request->code,
                'redirect_uri' => $request->redirect_uri,
                'code_verifier' => $request->code_verifier,
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('Wallet OAuth token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to exchange authorization code',
                ], 400);
            }

            $tokens = $tokenResponse->json();

            if (empty($tokens['access_token'])) {
                Log::error('Wallet OAuth: no access token', [
                    'status' => $tokenResponse->status(),
                    'error' => $tokens['error'] ?? $tokens['message'] ?? 'unknown',
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get access token',
                ], 400);
            }

            // Fetch user data from Kimlik.az
            $userResponse = Http::withToken($tokens['access_token'])
                ->get("{$walletApiUrl}/oauth/user");

            if (!$userResponse->successful()) {
                Log::error('Wallet OAuth user fetch failed', [
                    'status' => $userResponse->status(),
                    'body' => $userResponse->body(),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch user data',
                ], 400);
            }

            $walletUser = $userResponse->json()['data'];

            // Find or create user
            $user = User::where('wallet_id', $walletUser['id'])
                ->orWhere('email', $walletUser['email'])
                ->first();

            if ($user) {
                $user->update([
                    'wallet_id' => $walletUser['id'],
                    'wallet_access_token' => $tokens['access_token'],
                    'wallet_refresh_token' => $tokens['refresh_token'] ?? null,
                    'name' => $walletUser['name'],
                    'phone' => $walletUser['phone'] ?? $user->phone,
                ]);
            } else {
                $user = User::create([
                    'name' => $walletUser['name'],
                    'email' => $walletUser['email'] ?? $walletUser['id'] . '@wallet.user',
                    'phone' => $walletUser['phone'] ?? null,
                    'wallet_id' => $walletUser['id'],
                    'wallet_access_token' => $tokens['access_token'],
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
        } catch (\Exception $e) {
            Log::error('Wallet OAuth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication failed: ' . $e->getMessage(),
            ], 500);
        }
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
