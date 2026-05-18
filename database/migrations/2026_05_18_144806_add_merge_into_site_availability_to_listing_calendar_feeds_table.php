<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_calendar_feeds', function (Blueprint $table) {
            $table->boolean('merge_into_site_availability')
                ->default(false)
                ->after('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('listing_calendar_feeds', function (Blueprint $table) {
            $table->dropColumn('merge_into_site_availability');
        });
    }
};
