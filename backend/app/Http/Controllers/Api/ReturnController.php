<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnController extends Controller
{
    /**
     * GET /api/v1/returns/eligible-orders
     * Returns delivered order items within the 7-day return window.
     */
    public function eligibleOrders()
    {
        $user = Auth::user();

        $items = OrderItem::with(['order:id,order_number,total_amount,delivered_at'])
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', 'delivered')
                  ->where('delivered_at', '>=', now()->subDays(7));
            })
            ->whereDoesntHave('returnRequest') // not already returned
            ->get()
            ->map(function (OrderItem $item) {
                $daysLeft = max(0, 7 - now()->diffInDays($item->order->delivered_at));
                return [
                    'id'              => $item->id,
                    'product_id'      => $item->product_id,
                    'product_name'    => $item->product_name,
                    'product_image'   => $item->product_image,
                    'quantity'        => $item->quantity,
                    'unit_price'      => $item->unit_price,
                    'order_number'    => $item->order->order_number,
                    'total_amount'    => $item->order->total_amount,
                    'delivered_at'    => $item->order->delivered_at,
                    'return_days_left'=> $daysLeft,
                ];
            });

        return response()->json($items);
    }

    /**
     * POST /api/v1/returns
     * Create a return/refund request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'      => 'required|exists:orders,id',
            'type'          => 'required|in:return,exchange,refund_only,repair',
            'reason'        => 'required|string|max:255',
            'description'   => 'required|string|min:20|max:2000',
            'refund_method' => 'required|in:wallet,bkash,nagad,rocket,bank',
        ]);

        $user  = Auth::user();
        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', $user->id)
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', now()->subDays(7))
            ->firstOrFail();

        if (ReturnRequest::where('order_id', $order->id)->whereIn('status', ['pending', 'approved'])->exists()) {
            return response()->json(['message' => 'A return request for this order is already open.'], 422);
        }

        $return = ReturnRequest::create([
            'user_id'       => $user->id,
            'order_id'      => $order->id,
            'type'          => $validated['type'],
            'reason'        => $validated['reason'],
            'description'   => $validated['description'],
            'refund_method' => $validated['refund_method'],
            'status'        => 'pending',
        ]);

        return response()->json([
            'message' => 'Return request submitted successfully.',
            'data'    => $return,
        ], 201);
    }

    /**
     * GET /api/v1/returns
     * List the authenticated user's return requests.
     */
    public function index()
    {
        $returns = ReturnRequest::with('order:id,order_number')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return response()->json($returns);
    }
}
