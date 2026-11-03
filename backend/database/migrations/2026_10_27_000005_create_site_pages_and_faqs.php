<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

// Admin-editable storefront content: About / Privacy / Terms / Return pages and the FAQ list.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title');
            $table->longText('content')->nullable();   // empty = the storefront shows its built-in default text
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('site_pages')->insert([
            ['slug' => 'about',   'title' => 'আমাদের সম্পর্কে',        'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'privacy', 'title' => 'গোপনীয়তা নীতি',         'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'terms',   'title' => 'শর্তাবলী ও নিয়মনীতি',    'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'return',  'title' => 'রিটার্ন ও রিফান্ড নীতি', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $faqs = [
            ['অর্ডার করার পর কত দিনে ডেলিভারি পাব?', 'ঢাকায় ১-২ কার্যদিবস এবং সারাদেশে ২-৫ কার্যদিবসের মধ্যে ডেলিভারি দেওয়া হয়।', 'delivery'],
            ['পণ্য ফেরত দিতে চাইলে কী করব?', 'পণ্য পাওয়ার ৭ দিনের মধ্যে অ্যাকাউন্ট থেকে রিটার্ন রিকোয়েস্ট করুন।', 'return'],
            ['bKash দিয়ে কিভাবে পেমেন্ট করব?', 'চেকআউটে অনলাইন পেমেন্ট বেছে নিন; পেমেন্ট পেজে bKash সিলেক্ট করে সম্পন্ন করুন।', 'payment'],
            ['ক্যাশ অন ডেলিভারি পাওয়া যাবে কি?', 'হ্যাঁ, সারাদেশে ক্যাশ অন ডেলিভারি সুবিধা আছে (অ্যাডমিন চালু রাখলে)।', 'payment'],
        ];
        foreach ($faqs as $i => [$q, $a, $c]) {
            DB::table('faqs')->insert(['question' => $q, 'answer' => $a, 'category' => $c, 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('site_pages');
    }
};
