<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_visit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('volunteer_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes');
            $table->json('images')->nullable();
            $table->string('recommendation'); // approve|reject
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_visit_reports');
    }
};
