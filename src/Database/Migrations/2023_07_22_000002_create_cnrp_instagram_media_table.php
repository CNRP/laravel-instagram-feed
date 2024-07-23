<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cnrp_instagram_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_post_id')->constrained('cnrp_instagram_posts')->onDelete('cascade');
            $table->string('instagram_media_id');
            $table->string('media_type')->nullable();
            $table->string('url');
            $table->string('thumbnail_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cnrp_instagram_media');
    }
};