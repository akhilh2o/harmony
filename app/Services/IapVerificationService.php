<?php

namespace App\Services;

use App\Models\IapReceipt;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IapVerificationService
{
    // ─── ANDROID: Verify with Google Play Developer API ──────
    public function verifyAndroid(string $productId, string $purchaseToken): array
    {
        try {
            // Step 1: Get Google OAuth access token
            $accessToken = $this->getGoogleAccessToken();
            if (!$accessToken) {
                Log::error('IAP Android: Could not get Google access token');
                return ['valid' => false, 'error' => 'Could not authenticate with Google'];
            }

            $packageName = config('iap.android_package_name'); // com.yourapp.name

            // Step 2: Call Google Play API
            $response = Http::withToken($accessToken)
                ->get("https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{$packageName}/purchases/subscriptions/{$productId}/tokens/{$purchaseToken}");

            if (!$response->successful()) {
                Log::error('IAP Android verify failed', ['status' => $response->status(), 'body' => $response->body()]);
                return ['valid' => false, 'error' => 'Google verification failed: ' . $response->status()];
            }

            $data = $response->json();

            /*
             * paymentState:
             *   0 = Payment pending
             *   1 = Payment received
             *   2 = Free trial
             *   3 = Pending deferred upgrade/downgrade
             */
            $paymentState   = $data['paymentState'] ?? -1;
            $expiryMs       = $data['expiryTimeMillis'] ?? 0;
            $expiryAt       = $expiryMs ? \Carbon\Carbon::createFromTimestampMs($expiryMs) : null;
            $cancelReason   = $data['cancelReason'] ?? null;       // 0=user, 1=system, 2=replaced, 3=developer
            $autoRenewing   = $data['autoRenewing'] ?? false;
            $orderId        = $data['orderId'] ?? null;
            $countryCode    = $data['countryCode'] ?? null;
            $priceAmountMicros = $data['priceAmountMicros'] ?? null;
            $priceCurrencyCode = $data['priceCurrencyCode'] ?? null;

            $isValid = in_array($paymentState, [1, 2]) && ($expiryAt === null || $expiryAt->isFuture());

            // Determine status
            $status = 'active';
            if ($cancelReason !== null) $status = 'cancelled';
            if ($expiryAt && $expiryAt->isPast()) $status = 'expired';

            return [
                'valid'          => $isValid,
                'status'         => $status,
                'expires_at'     => $expiryAt,
                'order_id'       => $orderId,
                'auto_renewing'  => $autoRenewing,
                'country_code'   => $countryCode,
                'price_amount'   => $priceAmountMicros ? $priceAmountMicros / 1_000_000 : null,
                'price_currency' => $priceCurrencyCode,
                'environment'    => isset($data['purchaseType']) && $data['purchaseType'] === 0 ? 'sandbox' : 'production',
                'raw'            => $data,
            ];
        } catch (\Exception $e) {
            Log::error('IAP Android verify exception', ['message' => $e->getMessage()]);
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── iOS: Verify with Apple ───────────────────────────────
    public function verifyIos(string $receiptData): array
    {
        try {
            $sharedSecret = config('iap.apple_shared_secret');

            // Try production first, fallback to sandbox
            $result = $this->callAppleVerify($receiptData, $sharedSecret, false);

            // Status 21007 = sandbox receipt sent to production → retry with sandbox
            if (isset($result['status']) && $result['status'] === 21007) {
                $result = $this->callAppleVerify($receiptData, $sharedSecret, true);
                $environment = 'sandbox';
            } else {
                $environment = 'production';
            }

            if (!isset($result['status']) || $result['status'] !== 0) {
                Log::error('IAP iOS verify failed', ['status' => $result['status'] ?? 'unknown']);
                return ['valid' => false, 'error' => 'Apple verification failed. Status: ' . ($result['status'] ?? 'unknown')];
            }

            // Get latest receipt info (most recent subscription)
            $latestReceipts = $result['latest_receipt_info'] ?? [];
            if (empty($latestReceipts)) {
                return ['valid' => false, 'error' => 'No receipt info found'];
            }

            // Sort by expires_date_ms descending → get the latest
            usort($latestReceipts, fn ($a, $b) =>
                ($b['expires_date_ms'] ?? 0) <=> ($a['expires_date_ms'] ?? 0)
            );
            $latest = $latestReceipts[0];

            $expiryMs       = $latest['expires_date_ms'] ?? 0;
            $expiryAt       = $expiryMs ? \Carbon\Carbon::createFromTimestampMs((int) $expiryMs) : null;
            $productId      = $latest['product_id'] ?? null;
            $transactionId  = $latest['original_transaction_id'] ?? $latest['transaction_id'] ?? null;
            $cancellationDate = $latest['cancellation_date_ms'] ?? null;

            $isValid  = $expiryAt && $expiryAt->isFuture() && !$cancellationDate;
            $status   = $isValid ? 'active' : ($cancellationDate ? 'cancelled' : 'expired');

            return [
                'valid'          => $isValid,
                'status'         => $status,
                'expires_at'     => $expiryAt,
                'product_id'     => $productId,
                'transaction_id' => $transactionId,
                'environment'    => $environment,
                'price_amount'   => null, // Apple doesn't return price in verify response
                'price_currency' => null,
                'raw'            => $result,
            ];
        } catch (\Exception $e) {
            Log::error('IAP iOS verify exception', ['message' => $e->getMessage()]);
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── Activate user after successful verification ──────────
    public function activateUser(User $user, array $verificationResult, array $input): IapReceipt
    {
        $plan = SubscriptionPlan::where('slug', $input['plan_slug'])
            ->orWhere('iap_product_id', $input['product_id'] ?? '')
            ->first();

        $expiresAt = $verificationResult['expires_at']
            ?? ($plan ? $plan->getExpiresAt() : now()->addMonth());

        // Upsert receipt — agar same transaction_id hai toh update karo
        $receipt = IapReceipt::updateOrCreate(
            ['transaction_id' => $input['transaction_id']],
            [
                'user_id'        => $user->id,
                'platform'       => $input['platform'],
                'product_id'     => $input['product_id'] ?? $plan?->iap_product_id ?? $input['plan_slug'],
                'plan_slug'      => $plan?->slug ?? $input['plan_slug'],
                'purchase_token' => $input['purchase_token'] ?? null,
                'order_id'       => $verificationResult['order_id'] ?? null,
                'receipt_data'   => $input['receipt'] ?? null,
                'status'         => $verificationResult['status'] ?? 'active',
                'environment'    => $verificationResult['environment'] ?? 'production',
                'price_amount'   => $verificationResult['price_amount'] ?? null,
                'price_currency' => $verificationResult['price_currency'] ?? null,
                'purchase_at'    => now(),
                'expires_at'     => $expiresAt,
                'verified_at'    => now(),
                'raw_response'   => $verificationResult['raw'] ?? null,
            ]
        );

        // Update user subscription
        $user->update([
            'is_subscribed'           => true,
            'subscription_plan'       => $plan?->slug ?? $input['plan_slug'],
            'subscription_expires_at' => $expiresAt,
            'iap_transaction_id'      => $input['transaction_id'],
            'iap_platform'            => $input['platform'],
            'iap_purchase_token'      => $input['purchase_token'] ?? null,
        ]);

        Log::info('IAP: User subscription activated', [
            'user_id'    => $user->id,
            'plan'       => $plan?->slug,
            'expires_at' => $expiresAt,
            'platform'   => $input['platform'],
        ]);

        return $receipt;
    }

    // ─── Renewal: re-verify existing receipt ─────────────────
    public function renewIfValid(User $user): bool
    {
        if (!$user->iap_platform || !$user->iap_purchase_token && !$user->iap_transaction_id) {
            return false;
        }

        // Get latest receipt from DB
        $receipt = IapReceipt::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$receipt) return false;

        if ($user->iap_platform === 'android' && $receipt->purchase_token) {
            $result = $this->verifyAndroid($receipt->product_id, $receipt->purchase_token);
        } elseif ($user->iap_platform === 'ios' && $receipt->receipt_data) {
            $result = $this->verifyIos($receipt->receipt_data);
        } else {
            return false;
        }

        if (!$result['valid']) {
            // Subscription expired or cancelled
            $receipt->update(['status' => $result['status'] ?? 'expired']);
            $user->update([
                'is_subscribed'           => false,
                'subscription_expires_at' => $result['expires_at'] ?? null,
            ]);
            Log::info('IAP: Subscription expired for user', ['user_id' => $user->id]);
            return false;
        }

        // Still valid — update expiry
        $receipt->update([
            'expires_at'  => $result['expires_at'],
            'verified_at' => now(),
            'status'      => 'active',
        ]);
        $user->update([
            'is_subscribed'           => true,
            'subscription_expires_at' => $result['expires_at'],
        ]);
        return true;
    }

    // ─── Private helpers ──────────────────────────────────────

    private function getGoogleAccessToken(): ?string
    {
        try {
            $credentialsPath = config('iap.google_service_account_json');
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::error('IAP: Google service account JSON not found at: ' . $credentialsPath);
                return null;
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            $now  = time();
            $jwt  = $this->makeJwt($credentials, $now);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $response->json('access_token');
        } catch (\Exception $e) {
            Log::error('IAP: Google token error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function makeJwt(array $credentials, int $now): string
    {
        $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/androidpublisher',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $data = "$header.$payload";
        openssl_sign($data, $signature, $credentials['private_key'], 'SHA256');
        $sig = base64_encode($signature);

        return "$data.$sig";
    }

    private function callAppleVerify(string $receiptData, string $sharedSecret, bool $sandbox): array
    {
        $url = $sandbox
            ? 'https://sandbox.itunes.apple.com/verifyReceipt'
            : 'https://buy.itunes.apple.com/verifyReceipt';

        $response = Http::post($url, [
            'receipt-data'            => $receiptData,
            'password'                => $sharedSecret,
            'exclude-old-transactions'=> true,
        ]);

        return $response->json() ?? [];
    }
}