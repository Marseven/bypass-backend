<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('type_systeme')->default('process')->after('type');
            $table->string('niveau_sil')->default('na')->after('type_systeme');
            $table->string('fonction_securite')->nullable()->after('niveau_sil');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['type_systeme', 'niveau_sil', 'fonction_securite']);
        });
    }
};
