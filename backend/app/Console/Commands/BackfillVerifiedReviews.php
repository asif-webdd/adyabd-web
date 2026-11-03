<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Console\Command;

class BackfillVerifiedReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:backfill-verified';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill is_verified for existing reviews';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;
        Review::chunk(100, function ($reviews) use (&$count) {
            foreach ($reviews as $review) {
                $isVerified = OrderItem::whereHas('order', fn($q) => $q->where('user_id', $review->user_id)->where('status', 'delivered'))->where('product_id', $review->product_id)->exists();
                $review->updateQuietly(['is_verified' => $isVerified]);
                $count++;
            }
        });
        $this->info("Done! Updated {$count} reviews.");
    }
}
