<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_donors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('blood_group'); // A+, A-, B+, B-, AB+, AB-, O+, O-
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('last_donation_date')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['blood_group', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_donors');
    }
};
