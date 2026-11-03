<?php

namespace App\Observers;

use App\Models\{AdminUser, Order};
use App\Notifications\NewOrderNotification;

class OrderObserver
{
    /**
     * Notify all active admins when a new order is placed.
     */
    public function created(Order $order): void
    {
        $admins = AdminUser::where('is_active', true)->get();

        foreach ($admins as $admin) {
            $admin->notify(new NewOrderNotification($order));
        }
    }
}
