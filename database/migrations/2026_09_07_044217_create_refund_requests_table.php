<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending'); // pending|approved|rejected
            // How an approval is fulfilled - not in the spec's minimal schema,
            // but needed to actually implement the two paths spec §6 Phase 7
            // describes: "gateway refund or credit redirect to another campaign".
            $table->string('resolution_type')->nullable(); // gateway_refund|credit_redirect
            $table->foreignId('redirect_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
