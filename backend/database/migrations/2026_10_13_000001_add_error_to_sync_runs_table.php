<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_runs', function (Blueprint $table) {
            // The technical reason for a failure; `message` holds the plain-language one.
            $table->text('error')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('sync_runs', function (Blueprint $table) {
            $table->dropColumn('error');
        });
    }
};
