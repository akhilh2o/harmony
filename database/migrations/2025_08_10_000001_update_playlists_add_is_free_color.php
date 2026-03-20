<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->boolean('is_free')->default(true)->after('is_public');
            $table->string('color')->nullable()->after('is_free');
            $table->string('image')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->dropColumn(['is_free', 'color', 'image']);
        });
    }
};
