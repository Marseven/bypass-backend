<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing status values to CDC statuses
        // in_progress → active, completed → closed, cancelled → closed
        DB::table('requests')->where('status', 'in_progress')->update(['status' => 'active']);
        DB::table('requests')->where('status', 'completed')->update(['status' => 'closed']);
        DB::table('requests')->where('status', 'cancelled')->update(['status' => 'closed']);
    }

    public function down(): void
    {
        // Reverse: active → in_progress, closed → completed
        DB::table('requests')->where('status', 'active')->update(['status' => 'in_progress']);
        DB::table('requests')->where('status', 'closed')->update(['status' => 'completed']);
    }
};
