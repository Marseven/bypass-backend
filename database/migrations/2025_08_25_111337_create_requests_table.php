<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->foreignId('requester_id')->constrained('users')->onDelete('restrict');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'critical', 'emergency'])->default('normal');
            // $table->string('location');
            $table->foreignId('equipment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sensor_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'completed', 'cancelled'])
                  ->default('pending');
            $table->timestamp('submitted_at')->useCurrent();
            $table->enum('impact_securite', ['very_low', 'low', 'medium', 'high', 'very_high'])->default('low');
            $table->enum('impact_operationnel', ['very_low', 'low', 'medium', 'high', 'very_high'])->default('low');
            $table->enum('impact_environnemental', ['very_low', 'low', 'medium', 'high', 'very_high'])->default('low');
            $table->string('validation_required_by_role')->default('supervisor');
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->text('mesure_attenuation');
            $table->text('plan_contingence')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
