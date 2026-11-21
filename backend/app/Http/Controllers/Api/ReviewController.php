<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{
    /**
     * GET /api/v1/products/{id}/reviews
     * Public: list reviews for a product.
     */
    public function productReviews(int $productId)
    {
        $reviews = Review::with('user:id,name,avatar')->where('product_id', $productId)->where('is_approved', true)->latest()->paginate(10);
        return response()->json($reviews);
    }

    /**
     * GET /api/v1/reviews/my
     * Auth: list reviews submitted by the current user.
     */
    public function myReviews()
    {
        $reviews = Review::with(['product' => fn($query) => $query->with('primaryImage')->select(['id', 'name', 'slug'])])->where('user_id', Auth::id())->latest()->paginate(10);
        return response()->json($reviews);
    }

    /**
     * GET /api/v1/reviews/pending
     * Auth: order items the user has purchased (delivered) but not yet reviewed.
     */
    public function pendingReviews()
    {
        $userId = Auth::id();
        // Get delivered order items for this user that don't have a review yet
        $items = OrderItem::with('order:id,order_number,delivered_at')->whereHas('order', fn($q) => $q->where('user_id', $userId)->where('status', 'delivered'))->whereNotExists(function ($query) use ($userId) {
            $query->select('id')->from('reviews')->whereColumn('reviews.product_id', 'order_items.product_id')->where('reviews.user_id', $userId);
        })->latest()->get()->map(function (OrderItem $item) {
            $item->load('product');
            return [
                'id'            => $item->id,
                'product_id'    => $item->product_id,
                'product_slug'  => $item->product->slug,
                'product_name'  => $item->product_name,
                'product_image' => $item->product_image,
                'quantity'      => $item->quantity,
                'order_number'  => $item->order->order_number,
                'created_at'    => $item->order->delivered_at ?? $item->created_at,
            ];
        });


        return $this->success(data: $items, code: Response::HTTP_OK);
    }

    /**
     * POST /api/v1/reviews
     * Auth: store a new review (supports image uploads).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'order_item_id' => 'nullable|exists:order_items,id', //  keep consistent
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'required|string|min:5|max:2000',
            'images'        => 'sometimes|array|max:3',
            'images.*'      => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        if (!setting('enable_reviews', true)) {
            return $this->error('Reviews are currently disabled.', Response::HTTP_FORBIDDEN);
        }

        // Only customers who received the product can review it (verified purchase).
        $purchased = OrderItem::where('product_id', $validated['product_id'])
            ->whereHas('order', fn($q) => $q->where('user_id', Auth::id())->where('status', 'delivered'))
            ->first();
        if (!$purchased) {
            return $this->error('You can review a product only after it has been delivered to you.', Response::HTTP_FORBIDDEN);
        }

        if (Review::where('user_id', Auth::id())->where('product_id', $validated['product_id'])->exists()) {
            return $this->error('You have already reviewed this product.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Upload images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('reviews', 'public');
            }
        }

        $review = Review::create([
            'user_id'       => Auth::id(),
            'product_id'    => $validated['product_id'],
            'order_item_id' => $validated['order_item_id'] ?? $purchased->id,
            'is_approved'   => (bool) setting('auto_approve_reviews', false),
            'rating'        => $validated['rating'],
            'review'        => $validated['comment'],
            'images'        => $imagePaths ?: null,
        ]);

        return $this->success($review->load('user:id,name,avatar'), 'Review submitted successfully.', Response::HTTP_CREATED);

    }

    /**
     * PUT /api/v1/reviews/{id}
     */
    public function update(Request $request, int $id)
    {
        $review = Review::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $validated = $request->validate([
            'rating'  => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|min:5|max:2000',
        ]);

        $review->update($validated);
        return $this->success(data: $review, message: 'Review updated.', code: Response::HTTP_ACCEPTED);
    }

    /**
     * DELETE /api/v1/reviews/{id}
     */
    public function destroy(int $id)
    {
        $review = Review::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        // Remove images from storage
        if ($review->images) {
            foreach ($review->images as $img) {
                Storage::disk('public')->delete($img);
            }
        }

        $review->delete();
        return $this->success(data: [], message: 'Review deleted.', code: Response::HTTP_NO_CONTENT);
    }
}
