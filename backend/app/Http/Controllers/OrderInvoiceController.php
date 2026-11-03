<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderInvoiceController extends Controller
{
    /** Single order invoice — print or download */
    public function show(Request $request, Order $order)
    {
        $order->load(['user', 'seller.user', 'items', 'courierMerchant']);
        return view('filament.orders.invoice', compact('order'));
    }

    /** Bulk invoice — multiple orders, 2 per A4 page */
    public function bulk(Request $request)
    {
        $ids = array_filter(explode(',', $request->query('ids', '')));
        abort_if(empty($ids), 400, 'No order IDs provided.');
        $orders = Order::with(['user', 'seller.user', 'items', 'courierMerchant'])->whereIn('id', $ids)->orderBy('created_at', 'desc')->get();
        abort_if($orders->isEmpty(), 404, 'Orders not found.');

        return view('filament.orders.bulk-invoice', compact('orders'));
    }
}
