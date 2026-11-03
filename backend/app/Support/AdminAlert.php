<?php

namespace App\Support;

use App\Models\AdminUser;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Bell-icon notification for every admin (Filament database notifications, polled every 30s).
 * Used when a seller submits a product / withdrawal request / seller application.
 * Never throws: a notification problem must not break the customer-facing request.
 */
class AdminAlert
{
    public static function send(string $title, string $body = '', ?string $url = null, string $icon = 'heroicon-o-bell', string $color = 'warning'): void
    {
        try {
            $n = Notification::make()->title($title)->body($body)->icon($icon)->iconColor($color);
            if ($url) {
                $n->actions([Action::make('open')->label('Open')->url($url)->markAsRead()]);
            }
            $n->sendToDatabase(AdminUser::all(), true);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function url(callable $make): ?string
    {
        try {
            return $make();
        } catch (\Throwable) {
            return null;
        }
    }
}
