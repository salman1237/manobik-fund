<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('parameter_type'); // wbc_count|platelet|creatinine|pain_scale|milestone|hospital_days|...
            $table->string('label');
            $table->string('value');
            $table->string('unit')->nullable();
            $table->timestamp('recorded_at');
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['campaign_id', 'parameter_type', 'is_verified'], 'treatment_params_campaign_type_verified_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_parameters');
    }
};
