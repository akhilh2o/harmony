<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('playlist_audio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_audio_id')->constrained('session_audio','id')->onDelete('cascade');
            $table->integer('order')->default(0); // To maintain the order of audios in the playlist
            $table->boolean('is_active')->default(true); // To mark if the audio is active in the playlist
            $table->boolean('is_public')->default(false); // To mark if the audio is public in the playlist
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlist_audio');
    }
};
