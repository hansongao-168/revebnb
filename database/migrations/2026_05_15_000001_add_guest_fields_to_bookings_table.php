<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('guest_email')->nullable();
            $table->unsignedSmallInteger('guests')->nullable();
            $table->char('guest_access_token_hash', 64)->nullable();
            $table->timestamp('guest_access_token_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'guest_email',
                'guests',
                'guest_access_token_hash',
                'guest_access_token_expires_at',
            ]);
        });
    }
};
