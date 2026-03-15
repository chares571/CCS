<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('code_hash')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('verified_at')->nullable()->index();
            $table->timestamp('reset_at')->nullable()->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('resend_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('locked_at')->nullable()->index();
            $table->string('requested_ip', 45)->nullable();
            $table->string('verified_ip', 45)->nullable();
            $table->string('reset_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};

