<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seeker_id')->constrained('users')->cascadeOnDelete();
            $table->string('category'); // treatment|emergency|camp|education
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('hospital_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('bank_account_details')->nullable(); // encrypted:array
            $table->unsignedBigInteger('target_amount')->default(0); // smallest currency unit
            $table->unsignedBigInteger('raised_amount')->default(0); // smallest currency unit
            $table->string('status')->default('draft');
            // draft|pending_verification|field_visit|executive_review|published|funded|completed|rejected|cancelled
            $table->text('rejection_reason')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
