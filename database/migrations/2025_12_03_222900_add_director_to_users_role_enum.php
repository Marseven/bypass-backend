<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'enum pour ajouter 'director'
        // MySQL ne permet pas de modifier directement un enum, donc on utilise une requête SQL brute
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'supervisor', 'administrator', 'director') DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'director' de l'enum
        // Mettre à jour les utilisateurs avec 'director' vers 'administrator' avant de supprimer la valeur
        DB::statement("UPDATE `users` SET `role` = 'administrator' WHERE `role` = 'director'");
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'supervisor', 'administrator') DEFAULT 'user'");
    }
};





