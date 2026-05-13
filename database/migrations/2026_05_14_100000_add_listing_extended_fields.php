<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('landlord_id')->nullable()->after('tenant_id')->constrained('landlords')->nullOnDelete();
            $table->unsignedSmallInteger('min_nights')->default(1)->after('currency');
            $table->unsignedSmallInteger('max_guests')->nullable()->after('min_nights');
            $table->longText('guest_info_html')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
            $table->dropColumn(['min_nights', 'max_guests', 'guest_info_html']);
        });
    }
};
