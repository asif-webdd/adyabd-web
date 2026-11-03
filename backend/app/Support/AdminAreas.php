<?php

namespace App\Support;

/** The permission "areas" an admin role can be given. Every admin panel resource / page belongs to one area. */
class AdminAreas
{
    public const LABELS = [
        'orders'    => 'Orders, returns, couriers & delivery charges',
        'catalog'   => 'Products, categories, brands, coupons, flash sales, reviews',
        'customers' => 'Customers, IP blocks & auto-block rules',
        'sellers'   => 'Sellers & seller withdrawals',
        'finance'   => 'Transactions / wallet ledger',
        'content'   => 'Banners, blog, site pages, FAQs',
        'marketing' => 'Bulk notifications, SMS templates & SMS log',
        'support'   => 'Support tickets & contact messages',
        'hr'        => 'Employees, attendance, leave, payroll',
        'reports'   => 'Reports',
        'settings'  => 'Settings & appearance',
        'system'    => 'Activity log',
    ];

    // Admin users & roles are ALWAYS super-admin only (not listed above on purpose).
}
