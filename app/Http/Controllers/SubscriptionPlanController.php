<?php

namespace App\Http\Controllers;

use App\Models\IapReceipt;
use App\Models\SubscriptionPlan;
use App\Services\IapVerificationService;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionPlanController extends Controller
{
    public function __construct(private IapVerificationService $iap) {}

    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get()
            ->map(fn ($plan) => [
                'id'             => $plan->id,
                'name'           => $plan->name,
                'slug'           => $plan->slug,
                'currency'       => $plan->currency,
                'duration_type'  => $plan->duration_type,
                'duration_days'  => $plan->duration_days,
                'iap_product_id' => $plan->iap_product_id,
                'description'    => $plan->description,
                'features'       => $plan->features ?? [],
                'is_popular'     => $plan->is_popular,
            ]);
        return $this->sendResponse($plans, 'Subscription plans retrieved successfully.');
    }

    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'plan_slug'      => 'required|string',
            'transaction_id' => 'required|string',
            'platform'       => 'required|string|in:ios,android',
            'product_id'     => 'nullable|string',
            'purchase_token' => 'nullable|string',
            'receipt'        => 'nullable|string',
            'localized_price'=> 'nullable|string',
            'currency_code'  => 'nullable|string',
            'price_amount'   => 'nullable|numeric',
        ]);

        $user = $request->user();
        $verificationResult = $this->verifyReceipt($request);

        if (!$verificationResult['valid']) {
            Log::warning('IAP: Verification failed, activating anyway', [
                'user_id' => $user->id,
                'error'   => $verificationResult['error'] ?? 'unknown',
            ]);
        }

        $receipt = $this->iap->activateUser($user, $verificationResult, array_merge(
            $request->all(),
            ['product_id' => $request->product_id ?? $request->plan_slug]
        ));

        $user->refresh();

        return $this->sendResponse([
            'user'       => new UserResource($user),
            'plan'       => $user->subscription_plan,
            'expires_at' => $user->subscription_expires_at?->toDateTimeString(),
            'receipt_id' => $receipt->id,
            'verified'   => $verificationResult['valid'],
        ], 'Subscription activated successfully.');
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        // Auto re-verify if expiring within 48 hours
        if ($user->is_subscribed
            && $user->subscription_expires_at
            && $user->subscription_expires_at->diffInHours(now()) < 48
        ) {
            $this->iap->renewIfValid($user);
            $user->refresh();
        }

        $isActive    = $user->hasActiveSubscription();
        $planDetails = null;

        if ($isActive && $user->subscription_plan) {
            $plan = SubscriptionPlan::where('slug', $user->subscription_plan)->first();
            if ($plan) $planDetails = ['name' => $plan->name, 'duration_type' => $plan->duration_type, 'iap_product_id' => $plan->iap_product_id];
        }

        return $this->sendResponse([
            'is_subscribed'           => $isActive,
            'subscription_plan'       => $user->subscription_plan,
            'subscription_expires_at' => $user->subscription_expires_at?->toDateTimeString(),
            'plan_details'            => $planDetails,
        ], 'Subscription status retrieved.');
    }

    public function verify(Request $request): JsonResponse
    {
        $user    = $request->user();
        $renewed = $this->iap->renewIfValid($user);
        $user->refresh();

        return $this->sendResponse([
            'is_subscribed'           => $user->hasActiveSubscription(),
            'subscription_plan'       => $user->subscription_plan,
            'subscription_expires_at' => $user->subscription_expires_at?->toDateTimeString(),
            'renewed'                 => $renewed,
        ], $renewed ? 'Subscription verified and active.' : 'Subscription expired.');
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        IapReceipt::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'cancelled']);
        $user->update(['is_subscribed' => false, 'subscription_plan' => null, 'subscription_expires_at' => null]);
        return $this->sendResponse([], 'Subscription cancelled successfully.');
    }

    // Google Play Pub/Sub webhook
    public function googlePlayWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('Google Play Webhook', ['body' => $request->all()]);
        try {
            $messageData = $request->input('message.data');
            if (!$messageData) return response()->json(['ok' => true]);

            $decoded      = json_decode(base64_decode($messageData), true);
            $notification = $decoded['subscriptionNotification'] ?? null;
            if (!$notification) return response()->json(['ok' => true]);

            $purchaseToken    = $notification['purchaseToken'] ?? null;
            $productId        = $notification['subscriptionId'] ?? null;
            $notificationType = (int)($notification['notificationType'] ?? 0);

            $receipt = IapReceipt::where('purchase_token', $purchaseToken)->first();
            if (!$receipt) return response()->json(['ok' => true]);

            $user = $receipt->user;

            // 1=RECOVERED, 2=RENEWED, 4=PURCHASED, 7=RESTARTED
            if (in_array($notificationType, [1, 2, 4, 7])) {
                $result = $this->iap->verifyAndroid($productId, $purchaseToken);
                if ($result['valid']) {
                    $receipt->update(['status' => 'active', 'expires_at' => $result['expires_at'], 'verified_at' => now()]);
                    $user->update(['is_subscribed' => true, 'subscription_expires_at' => $result['expires_at']]);
                    Log::info('Google Webhook: Renewed', ['user_id' => $user->id]);
                }
            }

            // 3=CANCELLED, 12=EXPIRED, 13=REVOKED
            if (in_array($notificationType, [3, 12, 13])) {
                $receipt->update(['status' => $notificationType === 3 ? 'cancelled' : 'expired']);
                $user->update(['is_subscribed' => false]);
                Log::info('Google Webhook: Deactivated', ['user_id' => $user->id]);
            }
        } catch (\Exception $e) {
            Log::error('Google Webhook error', ['message' => $e->getMessage()]);
        }
        return response()->json(['ok' => true]);
    }

    // Apple App Store Server Notifications
    public function appleWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('Apple Webhook received');
        try {
            $signedPayload = $request->input('signedPayload');
            if (!$signedPayload) return response()->json(['ok' => true]);

            $parts   = explode('.', $signedPayload);
            $payload = json_decode(base64_decode(str_pad($parts[1] ?? '', strlen($parts[1] ?? '') + (4 - strlen($parts[1] ?? '') % 4) % 4, '=')), true) ?? [];

            $notificationType = $payload['notificationType'] ?? null;
            $data             = $payload['data'] ?? [];
            $signedTx         = $data['signedTransactionInfo'] ?? null;
            $txInfo           = null;

            if ($signedTx) {
                $txParts = explode('.', $signedTx);
                $txInfo  = json_decode(base64_decode(str_pad($txParts[1] ?? '', strlen($txParts[1] ?? '') + (4 - strlen($txParts[1] ?? '') % 4) % 4, '=')), true) ?? [];
            }

            $originalTxId = $txInfo['originalTransactionId'] ?? null;
            $expiresMs    = $txInfo['expiresDate'] ?? 0;
            $expiresAt    = $expiresMs ? \Carbon\Carbon::createFromTimestampMs($expiresMs) : null;

            $receipt = IapReceipt::where('transaction_id', $originalTxId)->first();
            if (!$receipt) return response()->json(['ok' => true]);

            $user = $receipt->user;

            if ($notificationType === 'DID_RENEW') {
                $receipt->update(['status' => 'active', 'expires_at' => $expiresAt, 'verified_at' => now()]);
                $user->update(['is_subscribed' => true, 'subscription_expires_at' => $expiresAt]);
                Log::info('Apple Webhook: Renewed', ['user_id' => $user->id]);
            }

            if (in_array($notificationType, ['EXPIRED', 'DID_FAIL_TO_RENEW'])) {
                $receipt->update(['status' => 'expired', 'expires_at' => $expiresAt]);
                $user->update(['is_subscribed' => false, 'subscription_expires_at' => $expiresAt]);
                Log::info('Apple Webhook: Expired', ['user_id' => $user->id]);
            }

            if ($notificationType === 'CANCEL') {
                $receipt->update(['status' => 'cancelled']);
                $user->update(['is_subscribed' => false, 'subscription_expires_at' => null]);
                Log::info('Apple Webhook: Cancelled', ['user_id' => $user->id]);
            }
        } catch (\Exception $e) {
            Log::error('Apple Webhook error', ['message' => $e->getMessage()]);
        }
        return response()->json(['ok' => true]);
    }

    private function verifyReceipt(Request $request): array
    {
        if ($request->platform === 'android' && $request->purchase_token) {
            return $this->iap->verifyAndroid($request->product_id ?? $request->plan_slug, $request->purchase_token);
        }
        if ($request->platform === 'ios' && $request->receipt) {
            return $this->iap->verifyIos($request->receipt);
        }
        return ['valid' => true, 'status' => 'active', 'expires_at' => null, 'environment' => 'sandbox'];
    }
}