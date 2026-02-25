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
        Schema::table('requests', function (Blueprint $table) {
            // Validation niveau 1 (supervisor)
            $table->foreignId('validated_by_level1_id')->nullable()->constrained('users')->onDelete('set null')->after('validated_by_id');
            $table->timestamp('validated_at_level1')->nullable()->after('validated_at');
            $table->enum('validation_status_level1', ['pending', 'approved', 'rejected'])->default('pending')->after('validated_at_level1');
            $table->text('rejection_reason_level1')->nullable()->after('validation_status_level1');
            
            // Validation niveau 2 (administrator/director)
            $table->foreignId('validated_by_level2_id')->nullable()->constrained('users')->onDelete('set null')->after('rejection_reason_level1');
            $table->timestamp('validated_at_level2')->nullable()->after('validated_by_level2_id');
            $table->enum('validation_status_level2', ['pending', 'approved', 'rejected'])->default('pending')->after('validated_at_level2');
            $table->text('rejection_reason_level2')->nullable()->after('validation_status_level2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['validated_by_level1_id']);
            $table->dropForeign(['validated_by_level2_id']);
            $table->dropColumn([
                'validated_by_level1_id',
                'validated_at_level1',
                'validation_status_level1',
                'rejection_reason_level1',
                'validated_by_level2_id',
                'validated_at_level2',
                'validation_status_level2',
                'rejection_reason_level2'
            ]);
        });
    }
};
