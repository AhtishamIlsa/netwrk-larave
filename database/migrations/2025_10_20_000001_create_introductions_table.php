<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('introductions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Who made the introduction
            $table->uuid('introduced_from_id');
            $table->string('introduced_from_email');
            $table->string('introduced_from_first_name')->nullable();
            $table->string('introduced_from_last_name')->nullable();

            // Introduced person A (could be an existing user or only an email)
            $table->uuid('introduced_id')->nullable();
            $table->string('introduced_email');
            $table->string('introduced_first_name')->nullable();
            $table->string('introduced_last_name')->nullable();
            $table->string('introduced_status')->default('pending');
            $table->boolean('introduced_is_attempt')->default(false);
            $table->string('introduced_message')->nullable();

            // Introduced person B (could be an existing user or only an email)
            $table->uuid('introduced_to_id')->nullable();
            $table->string('introduced_to_email');
            $table->string('introduced_to_first_name')->nullable();
            $table->string('introduced_to_last_name')->nullable();
            $table->string('introduced_to_status')->default('pending');
            $table->boolean('introduced_to_is_attempt')->default(false);
            $table->string('introduced_to_message')->nullable();

            // Overall
            $table->string('over_all_status')->default('pending');
            $table->string('request_status')->nullable();
            $table->text('message')->nullable();
            $table->text('reminder_message')->nullable();
            $table->boolean('revoke')->default(false);

            $table->timestamps();

            $table->index(['introduced_from_id']);
            $table->index(['introduced_id']);
            $table->index(['introduced_to_id']);
            $table->index(['introduced_email']);
            $table->index(['introduced_to_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('introductions');
    }
};


