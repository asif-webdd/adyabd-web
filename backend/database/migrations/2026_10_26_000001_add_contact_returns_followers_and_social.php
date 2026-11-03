<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables/columns that the API and the storefront already depended on but that
 * never had a migration: contact form, return requests, seller followers,
 * seller application details and social (Google) login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->string('subject');
            $table->string('order_id', 50)->nullable();   // order number typed by the visitor
            $table->text('message');
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->enum('type', ['return', 'exchange', 'refund_only', 'repair'])->default('return');
            $table->string('reason');
            $table->text('description');
            $table->enum('refund_method', ['wallet', 'bkash', 'nagad', 'rocket', 'bank'])->default('wallet');
            $table->enum('status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->index('status');
        });

        Schema::create('seller_followers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['seller_id', 'user_id']);
            $table->foreign('seller_id')->references('id')->on('sellers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Extra details collected by the "become a seller" form.
        Schema::table('sellers', function (Blueprint $table) {
            $table->json('application')->nullable();
        });

        // Social login: users may have no phone number yet.
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn('application');
        });
        Schema::dropIfExists('seller_followers');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('contact_messages');
    }
};
