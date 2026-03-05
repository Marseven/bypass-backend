<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_fa_secret', 255)->nullable()->after('is_active');
            $table->boolean('two_fa_enabled')->default(false)->after('two_fa_secret');
            $table->text('two_fa_backup_codes')->nullable()->after('two_fa_enabled');
            $table->timestamp('two_fa_verified_at')->nullable()->after('two_fa_backup_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_fa_secret', 'two_fa_enabled', 'two_fa_backup_codes', 'two_fa_verified_at']);
        });
    }
};
