<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emails are compared with their accents. The database's default comparison
 * ignores accents, so "jose@…" and "josé@…" would count as the same person and
 * one would get the other's laptops. Capitals are still ignored, so the database
 * keeps refusing "Alex.Smith@…" next to "alex.smith@…" as a second safety net
 * (P3, P4; finding F6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('email')->collation('utf8mb4_0900_as_ci')->change();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->string('last_owner_email')->nullable()->collation('utf8mb4_0900_as_ci')->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('email')->collation('utf8mb4_unicode_ci')->change();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->string('last_owner_email')->nullable()->collation('utf8mb4_unicode_ci')->change();
        });
    }
};
