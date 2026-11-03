<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use App\Models\Review;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Reviews')
                ->badge(fn() => Review::query()->count()),

            'pending' => Tab::make('Pending Approval')
                ->badge(fn() => Review::query()->where('is_approved', false)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($query) => $query->where('is_approved', false)),

            'approved' => Tab::make('Approved')
                ->badge(fn() => Review::query()->where('is_approved', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($query) => $query->where('is_approved', true)),

            'featured' => Tab::make('Featured')
                ->badge(fn() => Review::query()->where('is_featured', true)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn($query) => $query->where('is_featured', true)),

            'five_star' => Tab::make('5 Star')
                ->badge(fn() => Review::query()->where('rating', 5)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($query) => $query->where('rating', 5)),

            'low_rated' => Tab::make('1-2 Star')
                ->badge(fn() => Review::query()->whereIn('rating', [1, 2])->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($query) => $query->whereIn('rating', [1, 2])),

            'with_reply' => Tab::make('Replied')
                ->badge(fn() => Review::query()->whereNotNull('admin_reply')->count())
                ->modifyQueryUsing(fn($query) => $query->whereNotNull('admin_reply')),

            'no_reply' => Tab::make('No Reply')
                ->badge(fn() => Review::query()->whereNull('admin_reply')->count())
                ->modifyQueryUsing(fn($query) => $query->whereNull('admin_reply')),
        ];
    }
}
