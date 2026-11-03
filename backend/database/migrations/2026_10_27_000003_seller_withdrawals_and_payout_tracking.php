<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seller_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20);              // bkash | nagad | bank
            $table->string('account', 255);            // wallet number or "bank / account no"
            $table->enum('status', ['pending', 'paid', 'rejected'])->default('pending')->index();
            $table->string('reference', 100)->nullable();   // bKash TrxID / bank reference
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            // The payout is credited to the seller exactly once; refunds claw back at most what was credited.
            $table->timestamp('seller_credited_at')->nullable()->after('seller_payout');
            $table->decimal('seller_payout_reversed', 12, 2)->default(0)->after('seller_credited_at');
        });

        // Remember which lines were bought at a flash-sale price (so a cancel gives the sale units back).
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('flash_sale_product_id')->nullable()->after('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('flash_sale_product_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['seller_credited_at', 'seller_payout_reversed']);
        });
        Schema::dropIfExists('seller_withdrawals');
    }
};
