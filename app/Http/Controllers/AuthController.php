<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Requests\Auth\OtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;


class AuthController extends Controller
{

    public function redirectToProvider(Request $request, $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback(Request $request, $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed.'], 500);
        }

        $user = User::where('email', $socialUser->email)->first();

        if ($user) {
            // Update or link the social account to the existing user
            // Generate API token using Laravel Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;
        } else {
            // Create a new user if not found
            $user = User::create([
                'name' => $socialUser->name,
                'email' => $socialUser->email,
                'password' => null, // Social logins don't have passwords
                'oauth_uid' => $socialUser->id,
                'provider' => $provider, // Store the provider name
            ]);
            // Generate API token using Laravel Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;
        }

        return response()->json(['token' => $token, 'user' => $user]);
    }
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $userData = $request->validated();
            $user = User::create([
                'name'           => $userData['name'],
                'email'          => $userData['email'],
                'phone_code'     => $userData['phone_code'] ?? null,
                'phone'          => $userData['phone'] ?? null,
                'password'       => Hash::make($userData['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success'   => true,
                'data'      => new UserResource($user),
                'token'     => $token,
                'message'   => 'Registration successful.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login with email/password
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'token' => $token,
        ], 200);
    }

    /**
     * Social media login
     */
    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        try {
            $provider = $request->provider;
            $accessToken = $request->access_token;

            // Verify token with provider
            $userData = $this->verifySocialToken($provider, $accessToken);

            $user = User::firstOrCreate(
                ['provider' => $provider, 'provider_id' => $userData['id']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make(Str::random(16)),
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social login failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Request OTP for phone login
     */
    public function requestOtp(OtpRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrCreate([
            'phone' => $request->phone,
            'name' => $request->name ?? 'User_' . Str::random(6),
        ]);

        $otp = rand(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // Here you would integrate with an SMS service
        // For demo, we'll just return it
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'otp' => $otp, // Remove this in production
        ], 200);
    }

    /**
     * Verify OTP and login
     */
    public function verifyOtp(OtpRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)
            ->where('otp_code', $request->otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
            'phone_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'token' => $token,
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
        ], 200);
    }

    /**
     * Verify social media token
     */
    private function verifySocialToken(string $provider, string $accessToken): array
    {
        $url = match ($provider) {
            'google' => "https://www.googleapis.com/oauth2/v3/tokeninfo?access_token={$accessToken}",
            'facebook' => "https://graph.facebook.com/me?access_token={$accessToken}&fields=id,name,email",
            default => throw new \Exception('Unsupported provider'),
        };

        $response = Http::get($url);
        if ($response->failed()) {
            throw new \Exception('Invalid social token');
        }

        return $response->json();
    }
}
