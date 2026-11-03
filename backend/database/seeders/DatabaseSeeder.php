<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{Artisan, DB};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Must come FIRST: seeding users boots the Passport guard, which crashes with
        // "Invalid key supplied" if the oauth keys do not exist yet.
        $this->ensurePassport();

        $this->call([
            AdminUserSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            SellerSeeder::class,
            ProductSeeder::class,
            DivisionDistrictSeeder::class,
            OrderSeeder::class,
            ReviewSeeder::class,
            CouponSeeder::class,
            CourierMerchantSeeder::class,
            BannerSeeder::class,
            FlashSaleSeeder::class,
            SettingSeeder::class,
            DepartmentSeeder::class,
            // Admin-panel defaults: without these the Settings/Notification Templates/Auto-Block pages start empty.
            SettingsSeeder::class,
            NotificationTemplatesSeeder::class,
            AutoBlockRulesSeeder::class,
        ]);

        // Cached rating / review / sales numbers for the demo products and sellers
        \App\Models\Product::query()->select('id', 'seller_id')->chunkById(200, fn($rows) => $rows->each(fn($p) => $p->refreshStats(false)));
        \App\Models\Seller::query()->select('id')->chunkById(200, fn($rows) => $rows->each(fn($s) => $s->refreshStats()));

    }

    /**
     * Passport needs signing keys and a "personal access client" before any
     * API login can issue a token. Create them once, automatically (idempotent),
     * so `migrate:fresh --seed` always leaves a working API.
     */
    private function ensurePassport(): void
    {
        if (! file_exists(storage_path('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }

        $hasPersonalClient = DB::table('oauth_clients')
            ->where('grant_types', 'like', '%personal_access%')
            ->exists();

        if (! $hasPersonalClient) {
            Artisan::call('passport:client', [
                '--personal' => true,
                '--name'     => 'Bazar Personal Access Client',
                '--provider' => 'users',
                '--no-interaction' => true,
            ]);
        }
    }
}
