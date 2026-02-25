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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('criticite');
            $table->string('fabricant');
            $table->text('description')->nullable();
            $table->foreignId('zone_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['operational', 'maintenance', 'down', 'standby'])->default('operational');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
