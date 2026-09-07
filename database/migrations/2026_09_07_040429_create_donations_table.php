<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name');
            $table->string('donor_email');
            $table->unsignedBigInteger('amount'); // smallest currency unit
            $table->string('currency', 3);
            $table->string('gateway'); // stripe|shurjopay
            $table->string('transaction_id')->nullable()->unique();
            $table->string('status')->default('pending'); // pending|completed|refunded|failed
            $table->boolean('is_anonymous')->default(false);
            $table->json('gateway_meta')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
