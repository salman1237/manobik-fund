<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('assigned_volunteer_id')->nullable()->after('seeker_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('volunteer_assigned_at')->nullable()->after('assigned_volunteer_id');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_volunteer_id');
            $table->dropColumn('volunteer_assigned_at');
        });
    }
};
