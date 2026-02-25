<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('priority');
            $table->index(['status', 'priority']);
            $table->index(['status', 'end_time']);
            $table->index('submitted_at');
            $table->softDeletes();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->index('status');
            $table->softDeletes();
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->index('status');
            $table->index('equipment_id');
            $table->softDeletes();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['status', 'priority']);
            $table->dropIndex(['status', 'end_time']);
            $table->dropIndex(['submitted_at']);
            $table->dropSoftDeletes();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropSoftDeletes();
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['equipment_id']);
            $table->dropSoftDeletes();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
