<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->text('dangers_identifies');
            $table->json('mesures_compensatoires');
            $table->text('ipl_affectees')->nullable();
            $table->foreignId('validee_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->string('statut_validation')->default('pending');
            $table->text('motif_rejet')->nullable();
            $table->timestamps();

            $table->index('request_id');
            $table->index('statut_validation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oras');
    }
};
