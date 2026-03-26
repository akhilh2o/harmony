<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    /**
     * GET /subscription-plans
     * Public — returns active plans sorted by sort_order
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($plan) => [
                'id'               => $plan->id,
                'name'             => $plan->name,
                'slug'             => $plan->slug,
                'price'            => (float) $plan->price,
                'original_price'   => $plan->original_price ? (float) $plan->original_price : null,
                'formatted_price'  => $plan->formatted_price,
                'savings_percent'  => $plan->savings_percent,
                'currency'         => $plan->currency,
                'duration_type'    => $plan->duration_type,
                'duration_days'    => $plan->duration_days,
                'iap_product_id'   => $plan->iap_product_id,
                'description'      => $plan->description,
                'features'         => $plan->features ?? [],
                'is_popular'       => $plan->is_popular,
            ]);

        return $this->sendResponse($plans, 'Subscription plans retrieved successfully.');
    }

    /**
     * POST /user/subscription/activate
     * Protected — activate after IAP purchase
     * Body: { plan_slug, transaction_id, platform, receipt? }
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'plan_slug'      => 'required|string|exists:subscription_plans,slug',
            'transaction_id' => 'required|string',
            'platform'       => 'required|string|in:ios,android',
            'receipt'        => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::where('slug', $request->plan_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();
        $user->update([
            'is_subscribed'           => true,
            'subscription_plan'       => $plan->slug,
            'subscription_expires_at' => $plan->getExpiresAt(),
        ]);

        return $this->sendResponse([
            'user'             => new \App\Http\Resources\UserResource($user->fresh()),
            'plan'             => $plan->name,
            'expires_at'       => $user->fresh()->subscription_expires_at->toDateTimeString(),
        ], 'Subscription activated successfully.');
    }

    /**
     * GET /user/subscription
     * Protected — get current subscription status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $isActive = $user->hasActiveSubscription();

        $planDetails = null;
        if ($isActive && $user->subscription_plan) {
            $planDetails = SubscriptionPlan::where('slug', $user->subscription_plan)->first();
        }

        return $this->sendResponse([
            'is_subscribed'           => $isActive,
            'subscription_plan'       => $user->subscription_plan,
            'subscription_expires_at' => $user->subscription_expires_at?->toDateTimeString(),
            'plan_details'            => $planDetails ? [
                'name'          => $planDetails->name,
                'duration_type' => $planDetails->duration_type,
                'price'         => (float) $planDetails->price,
                'currency'      => $planDetails->currency,
            ] : null,
        ], 'Subscription status retrieved.');
    }

    /**
     * DELETE /user/subscription/cancel
     * Protected — cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $request->user()->update([
            'is_subscribed'           => false,
            'subscription_plan'       => null,
            'subscription_expires_at' => null,
        ]);

        return $this->sendResponse([], 'Subscription cancelled successfully.');
    }
}