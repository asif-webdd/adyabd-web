<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// গুরুত্বপূর্ণ: আপনার অর্ডার টেবিলের নাম 'orders' না হলে নিচের দুই জায়গায় বদলে নিন।
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // fbp, fbc, client_id, session_id, url, ip, user_agent
            $table->json('tracking_context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tracking_context');
        });
    }
};
