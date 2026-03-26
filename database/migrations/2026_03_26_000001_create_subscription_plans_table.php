<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g. "Monthly Plan"
            $table->string('slug')->unique();                // monthly | yearly | half_yearly
            $table->decimal('price', 10, 2);                 // e.g. 799.00
            $table->string('currency', 10)->default('INR');
            $table->string('duration_type');                 // monthly | yearly | half_yearly
            $table->integer('duration_days');                // 30 | 365 | 180
            $table->string('iap_product_id')->nullable();    // Play Store / App Store SKU
            $table->text('description')->nullable();
            $table->json('features')->nullable();            // ["Unlimited sessions", "3D audio", ...]
            $table->decimal('original_price', 10, 2)->nullable(); // for strikethrough
            $table->boolean('is_popular')->default(false);   // "Most Popular" badge
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};