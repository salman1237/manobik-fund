<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambulances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver_contact');
            $table->string('vehicle_type'); // van|micro|freezer|other
            $table->string('district');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_available')->default(true);
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['district', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambulances');
    }
};
