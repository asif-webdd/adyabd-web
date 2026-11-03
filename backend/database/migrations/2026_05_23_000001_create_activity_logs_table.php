<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->default('default')->index();
            $table->string('description');
            $table->string('event')->nullable()->index();        // created|updated|deleted|restored|login|logout
            $table->string('action')->nullable();               // human label e.g. "Status changed to Delivered"
            $table->nullableMorphs('subject');                  // what was acted on
            $table->nullableMorphs('causer');                   // who did it
            $table->string('causer_name')->nullable();          // snapshot of name at time of action
            $table->string('ip_address', 45)->nullable();       // IPv4 or IPv6
            $table->string('user_agent')->nullable();
            $table->json('properties')->nullable();             // {old:{}, attributes:{}}
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
