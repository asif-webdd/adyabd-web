<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        // Contact messages can now be answered from the admin panel.
        Schema::table('contact_messages', function (Blueprint $t) {
            if (!Schema::hasColumn('contact_messages', 'reply')) {
                $t->text('reply')->nullable();
                $t->string('reply_channel', 20)->nullable();   // sms | email | both
                $t->timestamp('replied_at')->nullable();
                $t->unsignedBigInteger('replied_by')->nullable();
            }
        });

        // Simple role based access for admin users. An admin WITHOUT a role keeps full access (so existing installs don't lock out).
        if (!Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function (Blueprint $t) {
                $t->id();
                $t->string('name')->unique();
                $t->string('description')->nullable();
                $t->boolean('is_super')->default(false);   // full access
                $t->json('permissions')->nullable();       // list of area keys, see App\Support\AdminAreas
                $t->timestamps();
            });
        }
        if (!Schema::hasColumn('admin_users', 'role_id')) {
            Schema::table('admin_users', fn(Blueprint $t) => $t->unsignedBigInteger('role_id')->nullable()->after('avatar'));
        }

        $now = now();
        foreach ([
            ['Super Admin', 'Full access to everything', true, []],
            ['Order Manager', 'Orders, returns, couriers and customer support', false, ['orders', 'support', 'customers']],
            ['Catalog & Content Editor', 'Products, categories, banners, blog, pages', false, ['catalog', 'content']],
            ['Accountant', 'Transactions, seller payouts and reports', false, ['finance', 'sellers', 'reports']],
            ['Support Agent', 'Tickets, contact messages and customers', false, ['support', 'customers']],
        ] as [$name, $desc, $super, $perms]) {
            if (!DB::table('admin_roles')->where('name', $name)->exists()) {
                DB::table('admin_roles')->insert([
                    'name' => $name, 'description' => $desc, 'is_super' => $super,
                    'permissions' => json_encode($perms), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        foreach ([
            ['support_reply', 'সাপোর্ট টিকেটে উত্তর', '{shop_name}: আপনার সাপোর্ট টিকেট #{ticket} এ উত্তর দেওয়া হয়েছে। দেখুন: {site_url}/support'],
            ['contact_reply', 'যোগাযোগ বার্তার উত্তর', '{shop_name}: {reply}'],
        ] as [$name, $title, $body]) {
            NotificationTemplate::firstOrCreate(['name' => $name], ['title' => $title, 'type' => 'sms', 'body' => $body, 'is_system' => true, 'is_active' => true]);
        }
    }

    public function down(): void
    {
    }
};
