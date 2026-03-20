<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_subscribed')->default(false)->after('is_active');
            $table->timestamp('subscription_expires_at')->nullable()->after('is_subscribed');
            $table->string('subscription_plan')->nullable()->after('subscription_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_subscribed', 'subscription_expires_at', 'subscription_plan']);
        });
    }
};
