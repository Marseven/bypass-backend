<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('bypass_type')->nullable()->after('status');
            $table->string('criticite')->nullable()->after('bypass_type');
            $table->string('duree_type')->nullable()->after('criticite');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['bypass_type', 'criticite', 'duree_type']);
        });
    }
};
