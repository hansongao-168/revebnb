<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landlord_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('landlords')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('renewal_email_sent_at')->nullable();
            $table->timestamps();

            $table->index(['landlord_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_access_tokens');
    }
};
