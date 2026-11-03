<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div class="flex flex-col gap-y-6">
        @include('filament.tracking.analytics', ['a' => $this->getAnalytics()])

        {{ $this->table }}
    </div>
</x-filament-panels::page>
