<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * products/sellers had these columns commented out in the first migration, yet the storefront sort
 * (sort=rating / sort=popular), several admin tables and the seller pages still read them -> SQL errors.
 * They are restored as cached statistics and kept up to date by Product::refreshStats()/Seller::refreshStats()
 * (called when a review is approved/changed and when an order is delivered/returned/cancelled).
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['products', 'sellers'] as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                if (!Schema::hasColumn($t, 'rating'))        $table->decimal('rating', 3, 2)->default(0);
                if (!Schema::hasColumn($t, 'total_reviews')) $table->unsignedInteger('total_reviews')->default(0);
                if (!Schema::hasColumn($t, 'total_sales'))   $table->unsignedInteger('total_sales')->default(0);
            });
        }

        // Backfill from existing data
        \App\Models\Product::withTrashed()->select('id', 'seller_id')->chunkById(200, fn($rows) => $rows->each(fn($p) => $p->refreshStats(false)));
        \App\Models\Seller::withTrashed()->select('id')->chunkById(200, fn($rows) => $rows->each(fn($s) => $s->refreshStats()));
    }

    public function down(): void
    {
        foreach (['products', 'sellers'] as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                foreach (['rating', 'total_reviews', 'total_sales'] as $c) {
                    if (Schema::hasColumn($t, $c)) $table->dropColumn($c);
                }
            });
        }
    }
};
