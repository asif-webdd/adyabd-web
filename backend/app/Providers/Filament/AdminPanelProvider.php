<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentAuthMiddleware;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Filament\{Panel, PanelProvider, Navigation\NavigationGroup};
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\{Authenticate, DisableBladeIconComponents, DispatchServingFilamentEvent};
use Illuminate\Cookie\Middleware\{AddQueuedCookiesToResponse, EncryptCookies};
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\{AuthenticateSession, StartSession};
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * @throws \Exception
     */
    public function panel(Panel $panel): Panel
    {
        // Same storage-backed logo/favicon the storefront uses (Appearance →
        // Branding), so uploading one image updates both the site and the
        // admin top bar — no redeploy needed. Guarded so a fresh install
        // (no DB/table yet) doesn't break panel boot.
        $logoPath = $faviconPath = '';
        $shopName = config('app.name');
        try {
            $settings    = Setting::all()->pluck('value', 'key');
            $logoPath    = (string) $settings->get('shop_logo', '');
            $faviconPath = (string) $settings->get('shop_favicon', '');
            $shopName    = (string) $settings->get('shop_name', $shopName);
        } catch (\Throwable $e) {
            // DB/table not ready yet — fall back to defaults above.
        }

        $toUrl = fn(string $path) => $path === '' ? null : (str_starts_with($path, 'http') ? $path : Storage::url($path));

        return $panel->default()->id('admin')->path('admin')->authMiddleware([
            FilamentAuthMiddleware::class
        ])->authGuard('admin')->login()->colors([
            'primary' => Color::hex('#006A4E'),
            'danger'  => Color::hex('#F42A41'),
            'warning' => Color::hex('#F59E0B'),
            'success' => Color::hex('#10B981'),
            'info'    => Color::hex('#3B82F6'),
        ])->brandName($shopName . ' Admin')
            ->brandLogo($toUrl($logoPath))
            ->brandLogoHeight('2rem')
            ->favicon($toUrl($faviconPath))
            ->darkMode(true)->sidebarCollapsibleOnDesktop()->globalSearch(true)
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])->databaseNotifications()
            ->databaseNotificationsPolling('30s')->resources([
                \App\Filament\Resources\OrderResource::class,
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\ProductResource::class,
                \App\Filament\Resources\SellerResource::class,
                \App\Filament\Resources\SellerWithdrawalResource::class,
                \App\Filament\Resources\SitePageResource::class,
                \App\Filament\Resources\FaqResource::class,
                \App\Filament\Resources\AdminUserResource::class,
                \App\Filament\Resources\AdminRoleResource::class,
                \App\Filament\Resources\CategoryResource::class,
                \App\Filament\Resources\BrandResource::class,
                \App\Filament\Resources\CourierMerchantResource::class,
                \App\Filament\Resources\IpBlockResource::class,
                \App\Filament\Resources\AutoBlockRuleResource::class,
                \App\Filament\Resources\CouponResource::class,
                \App\Filament\Resources\BannerResource::class,
                \App\Filament\Resources\FlashSaleResource::class,
                \App\Filament\Resources\ReviewResource::class,
                \App\Filament\Resources\SupportTicketResource::class,
                \App\Filament\Resources\NotificationTemplateResource::class,
                \App\Filament\Resources\BlogPostResource::class,
                \App\Filament\Resources\BlogCategoryResource::class,
                \App\Filament\Resources\BlogCommentResource::class,
                \App\Filament\Resources\BulkNotificationResource::class,
                \App\Filament\Resources\EmployeeResource::class,
                \App\Filament\Resources\DepartmentResource::class,
                \App\Filament\Resources\AttendanceResource::class,
                \App\Filament\Resources\LeaveResource::class,
                \App\Filament\Resources\PayrollResource::class,
                \App\Filament\Resources\TransactionResource::class,
                \App\Filament\Resources\SmsLogResource::class,
                \App\Filament\Resources\ActivityLogResource::class,
            ])->pages([
                \App\Filament\Pages\AppearancePage::class,
                \App\Filament\Pages\DashboardPage::class,
                \App\Filament\Pages\SettingsPage::class,
                \App\Filament\Pages\ProfilePage::class,
                \App\Filament\Pages\ReportPage::class,
            ])->widgets([
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\RevenueChartWidget::class,
                \App\Filament\Widgets\RecentOrdersWidget::class,
                \App\Filament\Widgets\OrderStatusDonutWidget::class,
                \App\Filament\Widgets\TopProductsWidget::class,
                \App\Filament\Widgets\BlockedUsersWidget::class,
                \App\Filament\Widgets\CourierSummaryWidget::class,
                \App\Filament\Widgets\SmsLogWidget::class,
            ])->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')->navigationGroups([
                NavigationGroup::make('Sales Management')->icon('heroicon-o-shopping-bag'),
                NavigationGroup::make('Product Management')->icon('heroicon-o-archive-box'),
                NavigationGroup::make('Customer Management')->icon('heroicon-o-users'),
                NavigationGroup::make('Content')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Notifications')->icon('heroicon-o-bell'),
                NavigationGroup::make('HRM')->icon('heroicon-o-identification'),
                NavigationGroup::make('Reports')->icon('heroicon-o-chart-bar'),
                NavigationGroup::make('Reports & Analytics')->icon('heroicon-o-presentation-chart-line'),
                NavigationGroup::make('Settings')->icon('heroicon-o-cog-6-tooth'),
            ])->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])->authMiddleware([Authenticate::class])
            ->renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, fn() => \App\Support\QueueHealth::banner())
            ->renderHook(\Filament\View\PanelsRenderHook::HEAD_END, fn() => view('filament.hooks.card-headers'));
    }
}
