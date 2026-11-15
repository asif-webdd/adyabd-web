<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // admin form never sent a title and offers "free shipping" (no value) -> relax the old NOT NULL / enum columns
            $table->string('title')->nullable()->change();
            $table->string('type', 20)->default('percentage')->change();
            $table->decimal('value', 10, 2)->default(0)->change();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->string('applicable_to', 20)->default('all')->after('is_for_new_users'); // all | categories | products
            $table->json('applicable_ids')->nullable()->after('applicable_to');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['applicable_to', 'applicable_ids']);
        });
    }
};
