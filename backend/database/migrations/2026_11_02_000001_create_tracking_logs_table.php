<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 50)->index();
            $table->string('event_id', 191)->index();
            $table->string('source', 10)->default('browser');   // browser | server
            $table->string('order_id', 64)->nullable()->index();
            $table->decimal('value', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('page_path', 255)->nullable();
            $table->boolean('has_error')->default(false)->index();
            $table->json('results')->nullable();                 // প্রতি platform-এর ফলাফল
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_logs');
    }
};
