<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_adults')->nullable()->after('max_guests');
            $table->unsignedSmallInteger('max_children')->default(0)->after('max_adults');
            $table->unsignedSmallInteger('max_infants')->default(0)->after('max_children');
            $table->unsignedSmallInteger('max_pets')->default(0)->after('max_infants');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('guest_adults')->nullable()->after('guests');
            $table->unsignedSmallInteger('guest_children')->default(0)->after('guest_adults');
            $table->unsignedSmallInteger('guest_infants')->default(0)->after('guest_children');
            $table->unsignedSmallInteger('guest_pets')->default(0)->after('guest_infants');
        });

        foreach (DB::table('listings')->orderBy('id')->lazyById() as $listing) {
            $maxGuests = $listing->max_guests !== null ? (int) $listing->max_guests : 16;

            DB::table('listings')->where('id', $listing->id)->update([
                'max_adults' => $maxGuests,
                'max_children' => $maxGuests,
                'max_infants' => min(5, $maxGuests),
                'max_pets' => 0,
            ]);
        }

        foreach (DB::table('bookings')->orderBy('id')->lazyById() as $booking) {
            $guests = $booking->guests !== null ? (int) $booking->guests : null;

            if ($guests === null) {
                continue;
            }

            DB::table('bookings')->where('id', $booking->id)->update([
                'guest_adults' => $guests,
                'guest_children' => 0,
                'guest_infants' => 0,
                'guest_pets' => 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropColumn(['max_adults', 'max_children', 'max_infants', 'max_pets']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['guest_adults', 'guest_children', 'guest_infants', 'guest_pets']);
        });
    }
};
