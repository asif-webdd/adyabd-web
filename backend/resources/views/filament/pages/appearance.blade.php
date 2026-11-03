<x-filament-panels::page>
    <style>
        .appearance-tabs .fi-fo-tabs {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            gap: 0 !important;
        }
        .appearance-tabs [role="tablist"] {
            display: flex !important;
            flex-direction: column !important;
            min-width: 180px !important;
            width: 180px !important;
            flex-shrink: 0 !important;
            border-bottom: none !important;
            border-right: 1px solid rgb(var(--gray-200)) !important;
            gap: 2px !important;
            padding: 6px !important;
            background: rgb(var(--gray-50)) !important;
            border-radius: 12px 0 0 12px !important;
            align-self: stretch !important;
        }
        .dark .appearance-tabs [role="tablist"] {
            border-right-color: rgb(var(--gray-700)) !important;
            background: rgb(var(--gray-900)) !important;
        }
        .appearance-tabs [role="tablist"] button[role="tab"] {
            justify-content: flex-start !important;
            text-align: left !important;
            border-bottom: none !important;
            border-radius: 8px !important;
            padding: 9px 12px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: rgb(var(--gray-600)) !important;
            transition: all 0.15s ease !important;
            white-space: nowrap !important;
            width: 100% !important;
        }
        .appearance-tabs [role="tablist"] button[role="tab"]:hover {
            background: rgb(var(--gray-800)) !important;
            color: rgb(var(--gray-900)) !important;
        }
        .appearance-tabs [role="tablist"] button[role="tab"][aria-selected="true"] {
            background: rgb(var(--primary-500)) !important;
            color: #fff !important;
            font-weight: 600 !important;
        }
        .appearance-tabs [role="tabpanel"] {
            flex: 1 !important;
            min-width: 0 !important;
            padding: 0 0 0 20px !important;
        }
        .appearance-tabs,
        .appearance-tabs > div,
        .appearance-tabs .fi-fo-component-ctn {
            width: 100% !important;
        }
        .appearance-tabs .fi-fo-tabs > div:first-child {
            border-bottom: none !important;
            padding-bottom: 0 !important;
        }
    </style>

    <form wire:submit="save" class="space-y-6">
        <div class="appearance-tabs w-full">{{ $this->form }}</div>
        <div class="flex gap-3 pt-2">
            <x-filament::button type="submit" color="success" icon="heroicon-o-check-circle">Save Appearance</x-filament::button>
            <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="resetToDefaults" wire:confirm="Reset all appearance settings to defaults?">Reset to Defaults</x-filament::button>
            <x-filament::button type="button" color="info" icon="heroicon-o-arrow-top-right-on-square" tag="a" :href="config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'))" target="_blank">Preview Storefront</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
