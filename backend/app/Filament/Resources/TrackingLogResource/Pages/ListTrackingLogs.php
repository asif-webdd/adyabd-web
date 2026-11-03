<?php

namespace App\Filament\Resources\TrackingLogResource\Pages;

use App\Filament\Resources\TrackingLogResource;
use App\Support\TrackingAnalytics;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListTrackingLogs extends ListRecords
{
    protected static string $resource = TrackingLogResource::class;

    protected static string $view = 'filament.tracking.list-tracking-logs';

    // তারিখ ফিল্টার (URL-এ থাকে, তাই রিলোড করলেও ফিল্টার ঠিক থাকে)
    #[Url(as: 'range')]
    public string $range = 'last_30_days';

    #[Url(as: 'from')]
    public ?string $from = null;

    #[Url(as: 'until')]
    public ?string $until = null;

    /** funnel | distribution */
    public string $funnelView = 'funnel';

    protected ?array $analyticsCache = null;

    public function updatedRange($value): void
    {
        if (! array_key_exists((string) $value, TrackingAnalytics::RANGES)) {
            $this->range = 'last_30_days';
        }
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedUntil(): void
    {
        $this->resetPage();
    }

    /** ভিউ থেকে ডাকা হয়: চার্ট, কার্ড, ফানেল সব এই একটা অ্যারে থেকে */
    public function getAnalytics(): array
    {
        return $this->analyticsCache ??= TrackingAnalytics::compute($this->range, $this->from, $this->until);
    }

    /** নিচের টেবিলও একই তারিখ-রেঞ্জে ফিল্টার হবে */
    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();
        if (! $query) {
            return $query;
        }

        [$start, $end] = TrackingAnalytics::resolve($this->range, $this->from, $this->until);

        return $query->whereBetween('created_at', TrackingAnalytics::dbRange($start, $end));
    }
}
