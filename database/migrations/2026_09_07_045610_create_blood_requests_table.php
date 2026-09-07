<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name');
            $table->string('requester_phone');
            $table->string('blood_group');
            $table->string('hospital_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('urgency')->default('normal'); // normal|urgent|critical
            $table->string('status')->default('open'); // open|fulfilled|cancelled
            $table->timestamps();

            $table->index(['blood_group', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
