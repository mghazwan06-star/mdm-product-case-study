<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Names, job titles and other descriptive text from Jamf are stored in full,
 * however long (P1: Jamf decides what's true; finding F1). Serials and emails
 * stay limited to 255 characters because they identify records; an entry with
 * a longer one is skipped with a reason instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('job_title')->nullable()->change();
            $table->text('phone')->nullable()->change();
            $table->text('username')->nullable()->change();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->text('external_id')->nullable()->change();
            $table->text('udid')->nullable()->change();
            $table->text('device_name')->change();
            $table->text('last_owner_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('job_title')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('username')->nullable()->change();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->string('external_id')->nullable()->change();
            $table->string('udid')->nullable()->change();
            $table->string('device_name')->change();
            $table->string('last_owner_name')->nullable()->change();
        });
    }
};
