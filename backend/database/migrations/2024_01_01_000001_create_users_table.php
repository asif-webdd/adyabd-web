<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 20)->unique();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            // Blocking fields
            $table->boolean('is_blocked')->default(false);
            $table->string('blocked_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->enum('block_type', ['manual', 'auto_cancel', 'auto_no_answer', 'auto_failed_delivery'])->nullable();
            // Behavior counters
            $table->integer('cancel_order_count')->default(0);
            $table->integer('failed_delivery_count')->default(0);
            $table->integer('no_answer_count')->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            // Wallet & referral
            $table->string('referral_code', 20)->unique()->nullable();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            // App tracking
            $table->string('fcm_token')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('device_type')->nullable();
            $table->string('preferred_language', 5)->default('bn');
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->index(['status', 'is_blocked']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
