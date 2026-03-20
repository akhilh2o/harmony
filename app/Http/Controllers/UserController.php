<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** GET /user/profile */
    public function profile(Request $request): JsonResponse
    {
        return $this->sendResponse(new UserResource($request->user()), 'Profile retrieved successfully.');
    }

    /** PUT /user/profile */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'       => 'sometimes|string|max:255',
            'email'      => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone_code' => 'nullable|string|max:10',
            'phone'      => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'avatar'     => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'phone_code', 'phone']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return $this->sendResponse(new UserResource($user->fresh()), 'Profile updated successfully.');
    }

    /** POST /user/change-password */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError('Current password is incorrect.', [], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return $this->sendResponse([], 'Password changed successfully.');
    }

    /** GET /user/subscription */
    public function subscriptionStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->sendResponse([
            'is_subscribed'           => $user->hasActiveSubscription(),
            'subscription_plan'       => $user->subscription_plan,
            'subscription_expires_at' => $user->subscription_expires_at?->toDateTimeString(),
        ], 'Subscription status retrieved.');
    }

    /** POST /user/subscription/activate  (called after successful IAP) */
    public function activateSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'plan'           => 'required|string|in:monthly,yearly',
            'transaction_id' => 'required|string',
            'platform'       => 'required|string|in:ios,android',
            'receipt'        => 'nullable|string',
        ]);

        $user = $request->user();
        $expiresAt = $request->plan === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        $user->update([
            'is_subscribed'           => true,
            'subscription_plan'       => $request->plan,
            'subscription_expires_at' => $expiresAt,
        ]);

        return $this->sendResponse(new UserResource($user->fresh()), 'Subscription activated successfully.');
    }

    /** DELETE /user/subscription/cancel */
    public function cancelSubscription(Request $request): JsonResponse
    {
        $request->user()->update([
            'is_subscribed'           => false,
            'subscription_plan'       => null,
            'subscription_expires_at' => null,
        ]);

        return $this->sendResponse([], 'Subscription cancelled successfully.');
    }
}
