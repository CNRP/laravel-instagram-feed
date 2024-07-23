<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cnrp_instagram_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('user_id')->nullable();
            $table->string('access_token')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->string('user_fullname')->nullable();
            $table->string('user_profile_picture')->nullable();
            $table->boolean('is_authorized')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cnrp_instagram_profiles');
    }
    
};