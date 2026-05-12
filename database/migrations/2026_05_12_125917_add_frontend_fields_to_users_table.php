<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->string('wechat_openid')->nullable()->unique();
            $table->unsignedTinyInteger('gender')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->boolean('is_admin')->default(false);
        });

        // Preserve panel access for accounts created before `is_admin` existed.
        DB::table('users')->update(['is_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'avatar',
                'wechat_openid',
                'gender',
                'status',
                'is_admin',
            ]);
        });
    }
};
