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
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            // Basic Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('position')->nullable();
            $table->string('company_name')->nullable();
            
            // Contact Information
            $table->string('phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->string('home_phone')->nullable();
            
            // Location
            $table->string('address')->nullable();
            $table->string('additional_addresses')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone')->nullable();
            
            // Additional Fields
            $table->string('title')->nullable();
            $table->string('role')->nullable();
            $table->string('website_url')->nullable();
            $table->string('birthday')->nullable();
            $table->text('notes')->nullable();
            
            // Array Fields (stored as JSON)
            $table->json('tags')->nullable();
            $table->json('industries')->nullable();
            $table->json('socials')->nullable();
            
            // Metadata
            $table->string('search_index')->nullable();
            $table->boolean('on_platform')->default(false);
            $table->boolean('has_sync')->default(false);
            $table->boolean('needs_sync')->default(false);
            
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('user_id');
            $table->index('email');
            $table->index('search_index');
            $table->index('on_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
