<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('placement');
            $table->string('footer_group')->nullable();
            $table->string('title');
            $table->string('link_type');
            $table->foreignId('site_page_id')->nullable()->constrained('site_pages')->restrictOnDelete();
            $table->string('route_name')->nullable();
            $table->json('route_params')->nullable();
            $table->string('external_url')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('target')->default('_self');
            $table->string('style_variant')->nullable();
            $table->string('active_match')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_navigation_items');
    }
};
