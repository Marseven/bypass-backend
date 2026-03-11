<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First: change ENUM to string to support new status values (draft, active, closed, expired)
        Schema::table('requests', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });

        // Then: migrate existing data to CDC statuses
        // in_progress → active, completed → closed, cancelled → closed
        DB::table('requests')->where('status', 'in_progress')->update(['status' => 'active']);
        DB::table('requests')->where('status', 'completed')->update(['status' => 'closed']);
        DB::table('requests')->where('status', 'cancelled')->update(['status' => 'closed']);
    }

    public function down(): void
    {
        // Reverse data first
        DB::table('requests')->where('status', 'active')->update(['status' => 'in_progress']);
        DB::table('requests')->where('status', 'closed')->update(['status' => 'completed']);
        DB::table('requests')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('requests')->where('status', 'expired')->update(['status' => 'pending']);

        // Then restore ENUM
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'completed', 'cancelled'])
                  ->default('pending')
                  ->change();
        });
    }
};
