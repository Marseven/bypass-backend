<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: create new table, copy data, drop old, rename new
            // This avoids FK reference issues that occur with rename approach
            DB::statement('PRAGMA foreign_keys=OFF');

            DB::statement('CREATE TABLE "users_new" (
                "id" integer primary key autoincrement not null,
                "username" varchar not null,
                "email" varchar not null,
                "email_verified_at" datetime,
                "password" varchar not null,
                "full_name" varchar not null,
                "role" varchar not null default \'user\',
                "is_active" tinyint(1) not null default 1,
                "remember_token" varchar,
                "created_at" datetime,
                "updated_at" datetime,
                "phone" varchar
            )');

            DB::statement('INSERT INTO "users_new" SELECT * FROM "users"');
            DB::statement('DROP TABLE "users"');
            DB::statement('ALTER TABLE "users_new" RENAME TO "users"');
            DB::statement('CREATE UNIQUE INDEX "users_username_unique" ON "users" ("username")');
            DB::statement('CREATE UNIQUE INDEX "users_email_unique" ON "users" ("email")');

            DB::statement('PRAGMA foreign_keys=ON');
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'supervisor', 'administrator', 'director') DEFAULT 'user'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE `users` SET `role` = 'administrator' WHERE `role` = 'director'");
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'supervisor', 'administrator') DEFAULT 'user'");
    }
};
