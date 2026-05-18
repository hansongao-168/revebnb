<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_calendar_feed_id')->constrained('listing_calendar_feeds')->cascadeOnDelete();
            $table->string('ical_uid', 255);
            $table->string('summary', 500)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('all_day')->default(false);
            $table->json('blocked_nights');
            $table->timestamps();

            $table->unique(['listing_calendar_feed_id', 'ical_uid'], 'ext_cal_events_feed_uid_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_calendar_events');
    }
};
