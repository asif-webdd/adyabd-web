<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seeds the initial admin accounts.
     *
     * The password is NEVER hard-coded. Set ADMIN_SEED_PASSWORD in .env to choose it;
     * otherwise a random one is generated and printed once. Re-running is safe
     * (existing admins are left untouched).
     */
    public function run(): void
    {
        $password = env('ADMIN_SEED_PASSWORD') ?: Str::password(16);

        $admins = [
            ['name' => 'Super Admin', 'email' => env('ADMIN_SEED_EMAIL', 'admin@bazar.com.bd'), 'phone' => '01700000001'],
        ];

        // Demo-only extra account: never created in production.
        if (! app()->environment('production')) {
            $admins[] = ['name' => 'Manager', 'email' => 'manager@example.com', 'phone' => '01700000002',
                'role_id' => DB::table('admin_roles')->where('name', 'Order Manager')->value('id')];
        }

        foreach ($admins as $admin) {
            if (DB::table('admin_users')->where('email', $admin['email'])->exists()) {
                continue;
            }
            DB::table('admin_users')->insert($admin + [
                'password'   => Hash::make($password),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command?->info("Admin created: {$admin['email']}");
        }

        if (! env('ADMIN_SEED_PASSWORD')) {
            $this->command?->warn("Generated admin password (shown once, change it after first login): {$password}");
        }
    }
}
