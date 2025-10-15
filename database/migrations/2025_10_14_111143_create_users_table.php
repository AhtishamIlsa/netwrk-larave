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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->text('avatar')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('website')->nullable();
            $table->text('location')->nullable();
            $table->text('bio')->nullable();
            $table->string('company_name')->nullable();
            $table->string('position')->nullable();
            $table->json('industries')->default('[]');
            $table->json('social_links')->default('{}');
            $table->json('socials_preference')->default('["linkedin", "instagram", "x"]');
            $table->string('city')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('otp_verified')->default(false);
            $table->string('role')->default('user');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};