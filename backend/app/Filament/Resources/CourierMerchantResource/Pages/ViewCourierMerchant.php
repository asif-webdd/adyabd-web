<?php

namespace App\Filament\Resources\CourierMerchantResource\Pages;

use App\Filament\Resources\CourierMerchantResource;
use App\Models\CourierMerchant;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\{Grid, Section, TextEntry};
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class ViewCourierMerchant extends ViewRecord
{
    protected static string $resource = CourierMerchantResource::class;

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        $status = $this->record->is_active ? 'Active' : 'Inactive';
        $default = $this->record->is_default ? ' · Default Courier' : '';

        return "Slug: {$this->record->slug} · Status: {$status}{$default}";
    }

    public function infolist(Infolist $infolist): Infolist
    {
        /** @var CourierMerchant $merchant */
        $merchant = $this->record;

        return $infolist->schema([

            // ── Profile Hero ──────────────────────────────────────
            Section::make()
                ->extraAttributes(['style' => 'position:relative; min-height:120px;'])
                ->schema([
                    Grid::make(5)
                        ->schema([
                            TextEntry::make('logo')->label('')->html()->getStateUsing(function (CourierMerchant $record) {
                                if (!$record->logo) {
                                    return '';
                                }

                                $url = Storage::url($record->logo);

                                return '<div style="position:absolute; top:0; left:0; right:auto; bottom:0; width:20%; display:flex; align-items:center; justify-content:center; overflow:hidden;">'
                                    . '<img src="' . e($url) . '" style="max-width:90%;max-height:64px;width:auto;height:auto;object-fit:contain;" />'
                                    . '</div>';
                            })->columnSpan(1),

                            Grid::make(4)->schema([
                                TextEntry::make('name')
                                    ->label('Courier Name')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-m-truck'),

                                TextEntry::make('slug')
                                    ->label('API Slug')
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-m-tag'),

                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn(bool $state) => $state ? 'Active' : 'Inactive')
                                    ->color(fn(bool $state) => $state ? 'success' : 'danger'),

                                TextEntry::make('is_default')
                                    ->label('Default Courier')
                                    ->badge()
                                    ->formatStateUsing(fn(bool $state) => $state ? ' Yes' : 'No')
                                    ->color(fn(bool $state) => $state ? 'warning' : 'gray'),

                                TextEntry::make('base_url')
                                    ->label('Base API URL')
                                    ->copyable()
                                    ->icon('heroicon-m-link')
                                    ->columnSpanFull(),
                            ])->columnSpan(4),
                        ]),
                ])->columnSpanFull(),

            // ── Performance Stats ──────────────────────────────────
            Section::make('Performance')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('total_deliveries')
                            ->label('Total Deliveries')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->icon('heroicon-m-archive-box'),

                        TextEntry::make('success_rate')
                            ->label('Success Rate')
                            ->suffix('%')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->badge()
                            ->color(fn($state) => $state >= 90 ? 'success' : ($state >= 70 ? 'warning' : 'danger')),

                        TextEntry::make('base_charge')
                            ->label('Base Charge')
                            ->money('BDT')
                            ->color('info'),

                        TextEntry::make('express_charge')
                            ->label('Express Charge')
                            ->money('BDT')
                            ->color('info'),
                    ]),

                    Grid::make(4)->schema([
                        TextEntry::make('cod_charge_percentage')
                            ->label('COD Charge')
                            ->suffix('%')
                            ->placeholder('—'),

                        TextEntry::make('supports_cod')
                            ->label('Cash on Delivery')
                            ->badge()
                            ->formatStateUsing(fn(bool $state) => $state ? ' Supported' : ' Not Supported')
                            ->color(fn(bool $state) => $state ? 'success' : 'gray'),

                        TextEntry::make('supports_same_day')
                            ->label('Same-Day Delivery')
                            ->badge()
                            ->formatStateUsing(fn(bool $state) => $state ? ' Supported' : ' Not Supported')
                            ->color(fn(bool $state) => $state ? 'success' : 'gray'),

                        TextEntry::make('sort_order')
                            ->label('Sort Order')
                            ->badge()
                            ->color('gray'),
                    ]),
                ])->columnSpanFull(),

            // ── API Credentials ─────────────────────────────────────
            Section::make('API Credentials')
                ->icon('heroicon-o-key')
                ->description('Get these details from your courier dashboard')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('api_key')
                            ->label('API Key')
                            ->copyable()
                            ->formatStateUsing(fn($state) => $state ? str_repeat('•', 6) . substr($state, -4) : '—')
                            ->icon('heroicon-m-key'),

                        TextEntry::make('api_secret')
                            ->label('API Secret')
                            ->copyable()
                            ->formatStateUsing(fn($state) => $state ? str_repeat('•', 6) . substr($state, -4) : '—')
                            ->icon('heroicon-m-lock-closed'),

                        TextEntry::make('api_username')
                            ->label('API Username')
                            ->placeholder('—'),

                        TextEntry::make('api_password')
                            ->label('API Password')
                            ->formatStateUsing(fn($state) => $state ? str_repeat('•', 8) : '—')
                            ->placeholder('—'),
                    ]),
                ])
                ->collapsible()
                ->columnSpanFull(),

            // ── Webhook Settings ─────────────────────────────────────
            Section::make('Webhook Settings')
                ->icon('heroicon-o-bolt')
                ->description('Use this URL to receive delivery status updates from the courier')
                ->schema([
                    TextEntry::make('webhook_url')
                        ->label('Webhook URL')
                        ->getStateUsing(fn(CourierMerchant $record) => $record->getWebhookUrl() ?? 'Not available')
                        ->copyable()
                        ->copyMessage('Webhook URL copied!')
                        ->icon('heroicon-m-link')
                        ->columnSpanFull(),

                    TextEntry::make('webhook_secret')
                        ->label('Webhook Secret')
                        ->getStateUsing(fn(CourierMerchant $record) => $record->getWebhookSecret()
                            ? str_repeat('•', 8) . substr($record->getWebhookSecret(), -6)
                            : 'Not generated yet')
                        ->icon('heroicon-m-shield-check')
                        ->color(fn(CourierMerchant $record) => $record->getWebhookSecret() ? 'success' : 'danger')
                        ->badge(),
                ])
                ->columnSpanFull(),

            // ── Extra Config ──────────────────────────────────────────
            Section::make('Extra Config')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    TextEntry::make('config_json')
                        ->label('')
                        ->getStateUsing(function (CourierMerchant $record) {
                            $config = $record->config;

                            if (is_string($config)) {
                                $config = json_decode($config, true);
                            }

                            return !empty($config) ? json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—';
                        })
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpanFull(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_webhook_secret')
                ->label('Generate Webhook Secret')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Generate New Webhook Secret')
                ->modalDescription('The old webhook URL will stop working. You will need to update the new URL in the courier panel.')
                ->action(function () {
                    /** @var CourierMerchant $merchant */
                    $merchant = $this->record;
                    $config = $merchant->config ?? [];
                    $config['webhook_secret'] = bin2hex(random_bytes(20));
                    $merchant->update(['config' => $config]);

                    Notification::make()
                        ->success()
                        ->title('New webhook secret generated')
                        ->body('Please update the new URL in the courier panel.')
                        ->send();

                    $this->refreshFormData(['config']);
                }),

            EditAction::make()->label('Edit'),
            Action::make("courier-list")->label("Back")->icon('heroicon-o-arrow-uturn-left')->url(static::getResource()::getUrl('index'))
        ];
    }
}
