<?php

namespace App\Providers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Schema, URL, Auth};
use App\Services\{CourierService, SmsService, PaymentService, BlockingService};
use App\Observers\AdminNotificationObserver;
use App\Models\{Order, Product, Seller, Setting, User, SupportTicket, Review, FlashSale, Coupon, SmsLog, Leave};

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CourierService::class);
        $this->app->singleton(SmsService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(BlockingService::class);
        require_once app_path('Helpers/helpers.php');
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Make public-disk file URLs follow the host the browser actually used (localhost vs 127.0.0.1,
        // custom domain, port…). A hard-coded APP_URL that differs from the admin's address made Filament's
        // file-upload previews (logo / favicon) hang on "Loading" because the browser blocked the cross-origin fetch.
        
        // (Only for the local disk. When uploads live on Cloudflare R2 the disk URL is AWS_URL and must stay as is.)
        if (!uploads_on_r2() && !app()->runningInConsole() && ($origin = request()->getSchemeAndHttpHost())) {
            config(['filesystems.disks.public.url' => rtrim($origin, '/') . '/storage']);
        }

        if (uploads_on_r2()) {
            // Livewire's temporary uploads must stay on the server's local disk (the default disk is R2 and
            // Livewire would then try to upload straight from the browser to the bucket, which needs extra CORS setup).
            config(['livewire.temporary_file_upload.disk' => 'local']);
            // R2 rejects public ACLs: Filament must store with "private" (no x-amz-acl: public-read). Files are still
            // public to visitors through the bucket's public URL (AWS_URL).
            \Filament\Forms\Components\FileUpload::configureUsing(fn(\Filament\Forms\Components\FileUpload $c) => $c->visibility('private'));
        }

        /*\Livewire\Livewire::component(
            'app.filament.resources.user-resource.relation-managers.orders-relation-manager',
            \App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager::class
        );
        \Livewire\Livewire::component(
            'app.filament.resources.user-resource.relation-managers.transactions-relation-manager',
            \App\Filament\Resources\UserResource\RelationManagers\TransactionsRelationManager::class
        );
        \Livewire\Livewire::component(
            'app.filament.resources.user-resource.relation-managers.reviews-relation-manager',
            \App\Filament\Resources\UserResource\RelationManagers\ReviewsRelationManager::class
        );
        \Livewire\Livewire::component(
            'app.filament.resources.user-resource.relation-managers.wishlists-relation-manager',
            \App\Filament\Resources\UserResource\RelationManagers\WishlistsRelationManager::class
        );
        \Livewire\Livewire::component(
            'app.filament.resources.user-resource.relation-managers.ip-blocks-relation-manager',
            \App\Filament\Resources\UserResource\RelationManagers\IpBlocksRelationManager::class
        );*/

        // Only override the guard for actual API requests.
        // Filament uses its own 'admin' guard — overriding globally
        // broke the database notification panel (always empty).
        if (request()->is('api/*') || request()->expectsJson()) {
            Auth::shouldUse('api');
        }

        // Single observer handles notifications + activity log for all models
        foreach ([Order::class, Product::class, Seller::class, User::class, SupportTicket::class, Review::class, FlashSale::class, Coupon::class, SmsLog::class, Leave::class] as $model) {
            $model::observe(AdminNotificationObserver::class);
        }

        Request::macro('isApi', function () {
            return $this->is('api/*') || $this->wantsJson();
        });

        try {
            if (Schema::hasTable('settings')) {
                $keys = [
                    'mail_mailer', 'mail_host', 'mail_port', 'mail_encryption',
                    'mail_username', 'mail_password',
                    'mail_from_address', 'mail_from_name',
                ];

                $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

                if ($settings->isNotEmpty()) {
                    config([
                        'mail.default'                 => $settings->get('mail_mailer') ?: config('mail.default'),
                        'mail.mailers.smtp.host'       => $settings->get('mail_host', config('mail.mailers.smtp.host')),
                        'mail.mailers.smtp.port'       => $settings->get('mail_port', config('mail.mailers.smtp.port')),
                        'mail.mailers.smtp.encryption' => $settings->get('mail_encryption', config('mail.mailers.smtp.encryption')),
                        'mail.mailers.smtp.username'   => $settings->get('mail_username', config('mail.mailers.smtp.username')),
                        'mail.mailers.smtp.password'   => $settings->get('mail_password', config('mail.mailers.smtp.password')),
                        'mail.from.address'            => $settings->get('mail_from_address', config('mail.from.address')),
                        'mail.from.name'               => $settings->get('mail_from_name', config('mail.from.name')),
                    ]);
                }
            }
        } catch (Exception $e) {
            // Silently fail during migrations/install
        }
    }
}
