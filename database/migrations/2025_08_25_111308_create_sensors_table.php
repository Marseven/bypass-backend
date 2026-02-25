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
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->string('type'); // pressure, temperature, vibration
            $table->string('unite');
            $table->string('seuil_critique');
            $table->dateTime('Dernier_Etallonnage');
            $table->enum('status', ['active', 'bypassed', 'maintenance', 'faulty', 'calibration'])->default('active');
            $table->decimal('last_reading', 10, 2)->nullable();
            $table->timestamp('last_reading_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
