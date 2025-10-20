<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();

            $table->index(['name']);
            $table->index(['country']);
            $table->unique(['name', 'state', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};


