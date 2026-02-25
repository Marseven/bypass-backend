<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('required_role');
            $table->unsignedInteger('level');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['request_id', 'level']);
            $table->index('status');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->boolean('requires_moc')->default(false)->after('commentaires');
            $table->timestamp('moc_triggered_at')->nullable()->after('requires_moc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_approvals');

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['requires_moc', 'moc_triggered_at']);
        });
    }
};
