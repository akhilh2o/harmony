<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iap_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('platform');                      // android | ios
            $table->string('product_id');                    // monthly_plan | yearly_plan
            $table->string('plan_slug')->nullable();         // monthly | yearly | half_yearly
            $table->string('transaction_id')->unique();      // Play Store / App Store transaction ID
            $table->string('purchase_token')->nullable();    // Android only
            $table->string('order_id')->nullable();          // Android order ID
            $table->text('receipt_data')->nullable();        // raw receipt (iOS) or token (Android)
            $table->string('status')->default('active');     // active | expired | cancelled | refunded
            $table->string('environment')->default('production'); // production | sandbox
            $table->decimal('price_amount', 10, 2)->nullable();   // actual amount charged
            $table->string('price_currency', 10)->nullable();     // INR | USD | GBP
            $table->timestamp('purchase_at')->nullable();         // when user bought
            $table->timestamp('expires_at')->nullable();          // when this period ends
            $table->timestamp('verified_at')->nullable();         // when we verified with store
            $table->json('raw_response')->nullable();             // full verification response
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['transaction_id']);
            $table->index(['purchase_token']);
        });

        // Add receipt tracking columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('iap_transaction_id')->nullable()->after('subscription_plan');
            $table->string('iap_platform')->nullable()->after('iap_transaction_id');      // android | ios
            $table->string('iap_purchase_token')->nullable()->after('iap_platform');      // Android renewal token
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iap_receipts');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['iap_transaction_id', 'iap_platform', 'iap_purchase_token']);
        });
    }
};