<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use App\Models\Banner;
use Filament\Actions;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Infolists\Components\{Section, TextEntry, ImageEntry, Grid};
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBanner extends ViewRecord
{
    protected static string $resource = BannerResource::class;

    public function getTitle(): string
    {
        return $this->record->title;
    }

    public function getSubheading(): ?string
    {
        $type = $this->record->type === 'slider' ? ' Slider' : ' Banner';
        $position = ucwords(str_replace('_', ' ', $this->record->position));
        $status = $this->record->is_active ? 'Active' : 'Inactive';
        return "{$type} · {$position} · {$status}";
    }

    // ── Header actions ───────────────────────────────────────────────────

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            // ── Image preview (full width) ───────────────────────────
            Section::make('Preview')->icon('heroicon-o-photo')->schema([
                ImageEntry::make('image')->label('Desktop Image')->extraImgAttributes([
                    'style' => 'width:100%;max-height:320px;object-fit:cover;border-radius:8px',
                ])->columnSpanFull(),
                ImageEntry::make('mobile_image')->label('Mobile Image')->extraImgAttributes([
                    'style' => 'max-height:200px;object-fit:cover;border-radius:8px',
                ])->visible(fn(Banner $record) => (bool)$record->mobile_image)->columnSpanFull(),
            ]),

            Grid::make(3)->schema([
                // ── Left: Content details ────────────────────────────
                Section::make('Content')->icon('heroicon-o-document-text')->columnSpan(2)->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('title')->label('Title (English)')->weight(FontWeight::SemiBold),
                        TextEntry::make('title_bn')->label('Title (Bangla)')->placeholder('—'),
                        TextEntry::make('subtitle')->label('Subtitle')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('link')->label('Click-through URL')->placeholder('No link set')->url(fn(Banner $record) => $record->link ?? '#')->openUrlInNewTab()->columnSpanFull(),
                    ]),
                ]),

                // ── Right: Settings ──────────────────────────────────
                Section::make('Settings')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('type')->label('Display type')->badge()->formatStateUsing(fn(string $state) => $state === 'slider' ? ' Slider' : ' Banner')->color(fn(string $state) => $state === 'slider' ? 'info' : 'gray'),
                        TextEntry::make('position')->label('Position')->badge()->formatStateUsing(fn(string $state) => match ($state) {
                            'hero'         => ' Hero',
                            'promo_top'    => ' Promo Top',
                            'promo_bottom' => ' Promo Bottom',
                            'sidebar'      => ' Sidebar',
                            'popup'        => ' Popup',
                            default        => $state,
                        })->color(fn(string $state) => match ($state) {
                            'hero'                      => 'primary',
                            'promo_top', 'promo_bottom' => 'warning',
                            'sidebar'                   => 'info',
                            'popup'                     => 'danger',
                            default                     => 'gray',
                        }),
                        TextEntry::make('slider_duration')->label('Slide duration')->formatStateUsing(fn($state, Banner $record) => $record->type === 'slider' ? "{$state} seconds" : '—'),
                        TextEntry::make('sort_order')->label('Sort order'),
                        TextEntry::make('is_active')->label('Status')->badge()->formatStateUsing(fn(bool $state) => $state ? 'Active' : 'Inactive')->color(fn(bool $state) => $state ? 'success' : 'danger'),
                    ]),
            ]),

            // ── Scheduling ───────────────────────────────────────────
            Section::make('Scheduling')
                ->icon('heroicon-o-calendar-days')
                ->columns(3)
                ->schema([
                    TextEntry::make('starts_at')->label('Show from')->dateTime('d M Y, h:i A')->placeholder('Immediately'),
                    TextEntry::make('ends_at')->label('Show until')->dateTime('d M Y, h:i A')->placeholder('Indefinitely'),
                    TextEntry::make('created_at')->label('Created')->dateTime('d M Y, h:i A')->hint(fn(Banner $record) => 'Updated ' . $record->updated_at->diffForHumans()),
                ]),
        ]);
    }

    // ── Infolist layout ──────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('toggle_type')
                ->label(fn() => $this->record->type === 'slider' ? ' Make Banner' : ' Make Slider')
                ->color(fn() => $this->record->type === 'slider' ? 'gray' : 'info')
                ->icon(fn() => $this->record->type === 'slider' ? 'heroicon-o-photo' : 'heroicon-o-squares-2x2')
                ->requiresConfirmation()
                ->modalHeading(fn() => $this->record->type === 'slider' ? 'Convert to Banner' : 'Convert to Slider')
                ->modalDescription(fn() => $this->record->type === 'slider' ? 'This item will be displayed as a static banner instead of a carousel slide.' : 'This item will join the rotating slider carousel for its position.')
                ->modalSubmitActionLabel('Yes, convert it')
                ->action(function () {
                    $newType = $this->record->type === 'slider' ? 'banner' : 'slider';
                    $this->record->update(['type' => $newType]);
                    $label = $newType === 'slider' ? 'Slider' : 'Banner';
                    Notification::make()->success()->title("Converted to {$label}")->send();
                    $this->refreshFormData(['type']);
                }),

            Actions\Action::make('toggle_active')
                ->label(fn() => $this->record->is_active ? ' Deactivate' : ' Activate')
                ->color(fn() => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    $state = $this->record->is_active ? 'activated' : 'deactivated';
                    Notification::make()->success()->title("Banner {$state}!")->send();
                }),

            Actions\DeleteAction::make()->successRedirectUrl(BannerResource::getUrl('index')),
            \Filament\Actions\Action::make("back")->label("Back")->icon('heroicon-m-arrow-left')->url(
                static::getResource()::getUrl('index')
            )->color('gray')
        ];
    }
}
