<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uniapp_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('placement');
            $table->string('title');
            $table->string('link_type');
            $table->foreignId('site_page_id')->nullable()->constrained('site_pages')->restrictOnDelete();
            $table->string('path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['placement', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniapp_navigation_items');
    }
};
