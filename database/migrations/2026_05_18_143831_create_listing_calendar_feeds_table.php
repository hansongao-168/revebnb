<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_calendar_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('source', 64)->nullable();
            $table->text('ical_url');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sync_interval_hours')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->string('last_sync_status', 32)->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();

            $table->index(['listing_id']);
            $table->index(['is_enabled', 'last_synced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_calendar_feeds');
    }
};
