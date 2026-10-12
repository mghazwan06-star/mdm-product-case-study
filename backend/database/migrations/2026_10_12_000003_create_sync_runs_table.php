<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('status', 16)->index();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->boolean('is_first_sync')->default(false);
            $table->json('counts')->nullable();
            $table->json('needs_attention')->nullable();
            $table->json('skipped')->nullable();
            $table->timestamp('stale_reference_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
