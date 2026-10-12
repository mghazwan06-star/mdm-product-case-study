<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('serial_code')->unique(); // stored uppercase
            $table->string('provider', 32)->index();  // e.g. "jamf"
            $table->string('external_id')->nullable();
            $table->string('udid')->nullable();
            $table->string('device_name');            // falls back to the model, then "Unknown device ({serial})"
            $table->string('status', 32)->default('assigned')->index();

            // Current owner. Deleting the employee keeps the laptop and clears this link.
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot of the last owner, kept when the link is cleared.
            $table->string('last_owner_name')->nullable();
            $table->string('last_owner_email')->nullable()->index();

            $table->json('attributes')->nullable();   // hardware specs and other Jamf details
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('last_report_at')->nullable();
            $table->timestamp('last_seen_in_mdm_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
