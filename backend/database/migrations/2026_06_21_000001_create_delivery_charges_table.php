<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_charges', function (Blueprint $table) {
            $table->id();
            $table->string('area_name');           // e.g. "Dhaka", "Khulna"
            $table->enum('area_type', ['division', 'district', 'upazila'])->default('district');
            $table->decimal('charge', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed common Bangladeshi districts with default charges
        DB::table('delivery_charges')->insert([
            ['area_name' => 'Dhaka',       'area_type' => 'district', 'charge' => 60,  'is_active' => true, 'sort_order' => 1,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Chittagong',  'area_type' => 'district', 'charge' => 100, 'is_active' => true, 'sort_order' => 2,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Sylhet',      'area_type' => 'district', 'charge' => 120, 'is_active' => true, 'sort_order' => 3,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Rajshahi',    'area_type' => 'district', 'charge' => 100, 'is_active' => true, 'sort_order' => 4,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Khulna',      'area_type' => 'district', 'charge' => 100, 'is_active' => true, 'sort_order' => 5,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Barishal',    'area_type' => 'district', 'charge' => 120, 'is_active' => true, 'sort_order' => 6,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Rangpur',     'area_type' => 'district', 'charge' => 120, 'is_active' => true, 'sort_order' => 7,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Mymensingh',  'area_type' => 'district', 'charge' => 100, 'is_active' => true, 'sort_order' => 8,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Gazipur',     'area_type' => 'district', 'charge' => 70,  'is_active' => true, 'sort_order' => 9,  'created_at' => now(), 'updated_at' => now()],
            ['area_name' => 'Narayanganj', 'area_type' => 'district', 'charge' => 70,  'is_active' => true, 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_charges');
    }
};
