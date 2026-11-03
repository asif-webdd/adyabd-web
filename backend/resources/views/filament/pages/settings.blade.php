<x-filament-panels::page>
    <style>
        /* ── Vertical Tabs Layout ─────────────────────────────── */

        /* Make the tab container flex row */
        .settings-vertical-tabs .fi-fo-tabs {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            gap: 0 !important;
        }

        /* Sidebar tablist */
        .settings-vertical-tabs [role="tablist"] {
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

        .dark .settings-vertical-tabs [role="tablist"] {
            border-right-color: rgb(var(--gray-700)) !important;
            background: rgb(var(--gray-900)) !important;
        }

        /* Tab buttons */
        .settings-vertical-tabs [role="tablist"] button[role="tab"] {
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

        .dark .settings-vertical-tabs [role="tablist"] button[role="tab"] {
            color: rgb(var(--gray-400)) !important;
        }

        .settings-vertical-tabs [role="tablist"] button[role="tab"]:hover {
            background: rgb(var(--gray-100)) !important;
            color: rgb(var(--gray-900)) !important;
        }

        .dark .settings-vertical-tabs [role="tablist"] button[role="tab"]:hover {
            background: rgb(var(--gray-800)) !important;
            color: rgb(var(--gray-100)) !important;
        }

        .settings-vertical-tabs [role="tablist"] button[role="tab"][aria-selected="true"] {
            background: rgb(var(--primary-500)) !important;
            color: #fff !important;
            font-weight: 600 !important;
        }

        /* Tab panel — takes remaining width */
        .settings-vertical-tabs [role="tabpanel"] {
            flex: 1 !important;
            min-width: 0 !important;
            padding: 0 0 0 20px !important;
        }

        /* Wrapper fills full width */
        .settings-vertical-tabs,
        .settings-vertical-tabs > div,
        .settings-vertical-tabs .fi-fo-component-ctn {
            width: 100% !important;
        }

        /* Remove weird top border/padding on tablist wrapper */
        .settings-vertical-tabs .fi-fo-tabs > div:first-child {
            border-bottom: none !important;
            padding-bottom: 0 !important;
        }
    </style>

    <form wire:submit.prevent class="space-y-6">
        <div class="settings-vertical-tabs w-full">
            {{ $this->form }}
        </div>

    </form>
</x-filament-panels::page>
