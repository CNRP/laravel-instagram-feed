<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cnrp_instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_profile_id')->nullable()->constrained('cnrp_instagram_profiles')->onDelete('set null');
            $table->string('instagram_id')->unique();
            $table->string('type')->nullable();
            $table->text('caption')->nullable();
            $table->string('permalink')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cnrp_instagram_posts');
    }
};