<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('listings')->whereNotNull('cover_image')->get(['id', 'cover_image']);
        foreach ($rows as $row) {
            DB::table('listing_images')->insert([
                'listing_id' => $row->id,
                'path' => $row->cover_image,
                'sort_order' => 0,
                'is_cover' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('listing_images')->truncate();
    }
};
