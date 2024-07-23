<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_id')->unique();
            $table->string('type')->nullable();
            $table->text('caption')->nullable();
            $table->string('permalink')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('instagram_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_post_id')->constrained('instagram_posts')->onDelete('cascade');
            $table->string('media_type')->nullable();
            $table->string('url');
            $table->string('thumbnail_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_media');
        Schema::dropIfExists('instagram_posts');
    }
};