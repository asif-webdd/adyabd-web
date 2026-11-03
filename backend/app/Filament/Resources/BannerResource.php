<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages\{CreateBanner, EditBanner, ListBanners, ViewBanner};
use App\Models\Banner;
use Filament\Forms\{Form,
    Get,
    Components\Section,
    Components\TextInput,
    Components\Select,
    Components\Toggle,
    Components\FileUpload,
    Components\DateTimePicker,
    Components\Grid,
    Components\Placeholder,
    Components\Actions as FormActions,
    Components\Actions\Action as FormAction
};
use Filament\Tables\{Actions\BulkAction,
    Actions\DeleteBulkAction,
    Actions\ViewAction,
    Filters\SelectFilter,
    Filters\TernaryFilter,
    Table,
    Columns\TextColumn,
    Columns\BadgeColumn,
    Columns\IconColumn,
    Columns\ImageColumn,
    Columns\ToggleColumn,
    Actions\EditAction,
    Actions\DeleteAction,
    Actions\Action
};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class BannerResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'content';
    protected static ?string $model           = Banner::class;
    protected static ?string $navigationIcon  = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?string $navigationLabel = 'Banners & Sliders';
    protected static ?int    $navigationSort  = 4;

    public static function getNavigationBadge(): ?string
    {
        $sliders = Banner::active()->sliders()->count();
        $banners = Banner::active()->banners()->count();

        return "{$sliders}S · {$banners}B";
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'info';
    }

    // ────────────────────────────────────────────────────────────────────
    // FORM
    // ────────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── Type switcher ────────────────────────────────────────
            Section::make('Display Type')->icon('heroicon-o-squares-2x2')->description('Choose how this item is displayed on the storefront.')->schema([
                Select::make('type')->label('Type')->options([
                    'banner' => 'Banner — single static image',
                    'slider' => 'Slider — auto-rotating carousel slide',
                ])->default('banner')->required()->native(false)->live()->helperText(fn(Get $get) => match ($get('type')) {
                    'slider' => 'This item will appear in the rotating carousel for the selected position. Multiple slider items at the same position rotate automatically.',
                    default  => 'This item displays as a single, static promotional banner.',
                })->columnSpan(1),
                TextInput::make('slider_duration')->label('Slide duration (seconds)')->numeric()->minValue(2)->maxValue(30)->default(5)->suffix('sec')->helperText('How long this slide is shown before moving to the next.')->visible(fn(Get $get) => $get('type') === 'slider')->columnSpan(1),
            ])->columns(2),

            // ── Content ──────────────────────────────────────────────
            Section::make('Content')->icon('heroicon-o-document-text')->schema([
                TextInput::make('title')->label('Title (English)')->required()->maxLength(120),
                TextInput::make('title_bn')->label('Title (Bangla)')->maxLength(120),
                TextInput::make('subtitle')->label('Subtitle / Tagline')->maxLength(200)->columnSpanFull(),
                TextInput::make('link')->label('Click-through URL')->url()->placeholder('https://example.com/sale')->helperText('Leave blank for non-clickable banners.')->columnSpanFull(),
            ])->columns(2),

            // ── Placement ────────────────────────────────────────────
            Section::make('Placement')->icon('heroicon-o-map-pin')->schema([
                Select::make('position')->label('Position')->options([
                    'hero'         => 'Hero (Main)',
                    'promo_top'    => 'Promo Top',
                    'promo_bottom' => 'Promo Bottom',
                    'sidebar'      => 'Sidebar',
                    'popup'        => 'Popup',
                ])->required()->default('hero')->native(false)->helperText(fn(Get $get) => $get('type') === 'slider' ? 'All slider items sharing this position rotate together.' : 'The page area where this banner appears.'),
                TextInput::make('sort_order')->label('Sort order')->numeric()->default(0)->helperText('Lower numbers appear first. Drag-to-reorder also available in the list.'),
            ])->columns(2),

            // ── Images ───────────────────────────────────────────────
            Section::make('Images')->icon('heroicon-o-photo')->schema([
                FileUpload::make('image')->label('Desktop Image')->image()->required()->directory('banners')->imageEditor()->imageEditorAspectRatios(['16:9', '4:1', '3:1'])->helperText(fn(Get $get) => $get('type') === 'slider' ? 'Recommended: 1920×600px (slider)' : 'Recommended: 1920×400px (banner)'),
                FileUpload::make('mobile_image')->label('Mobile Image')->image()->directory('banners')->imageEditor()->helperText('Recommended: 768×400px. Falls back to desktop image if empty.'),
            ])->columns(2),
            // ── Scheduling & Visibility ───────────────────────────────
            Section::make('Scheduling & Visibility')->icon('heroicon-o-calendar-days')->schema([
                DateTimePicker::make('starts_at')->label('Show from')->helperText('Leave blank to show immediately.')->native(false),
                DateTimePicker::make('ends_at')->label('Show until')->helperText('Leave blank to show indefinitely.')->native(false)->after('starts_at'),
                Toggle::make('is_active')->label('Active')->default(true)->helperText('Inactive items are hidden regardless of dates.')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────
    // TABLE
    // ────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('image')->width(100)->height(55)->extraImgAttributes(['style' => 'object-fit:cover;border-radius:6px']),
            TextColumn::make('title')->searchable()->sortable()->description(fn(Banner $r) => $r->subtitle ?? ''),
            BadgeColumn::make('type')->label('Type')->formatStateUsing(fn(string $state) => match ($state) {
                'slider' => 'Slider',
                default  => 'Banner',
            })->icon(fn(string $state) => match ($state) {
                'slider' => 'heroicon-o-squares-2x2',
                default  => 'heroicon-o-photo',
            })->color(fn(string $state) => match ($state) {
                'slider' => 'info',
                default  => 'gray',
            }),

            BadgeColumn::make('position')->label('Position')->formatStateUsing(fn(string $state) => match ($state) {
                'hero'         => 'Hero',
                'promo_top'    => 'Promo Top',
                'promo_bottom' => 'Promo Bottom',
                'sidebar'      => 'Sidebar',
                'popup'        => 'Popup',
                default        => $state,
            })->icon(fn(string $state) => match ($state) {
                'hero'         => 'heroicon-o-home',
                'promo_top'    => 'heroicon-o-megaphone',
                'promo_bottom' => 'heroicon-o-document-text',
                'sidebar'      => 'heroicon-o-bookmark',
                'popup'        => 'heroicon-o-bell',
                default        => 'heroicon-o-tag',
            })->color(fn(string $state) => match ($state) {
                'hero'                      => 'primary',
                'promo_top', 'promo_bottom' => 'warning',
                'sidebar'                   => 'info',
                'popup'                     => 'danger',
                default                     => 'secondary',
            }),
            TextColumn::make('slider_duration')->label('Duration')->formatStateUsing(fn($state, Banner $record) => $record->type === 'slider' ? "{$state}s" : '—')->sortable(),
            TextColumn::make('sort_order')->label('Order')->sortable(),
            TextColumn::make('starts_at')->label('Start')->dateTime('d M Y')->placeholder('—'),
            TextColumn::make('ends_at')->label('End')->dateTime('d M Y')->placeholder('—'),
            BadgeColumn::make('is_active')->label('Status')->formatStateUsing(fn(bool $state) => $state ? 'Active' : 'Inactive')->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')->color(fn(bool $state) => $state ? 'success' : 'danger'),
        ])->reorderable('sort_order')->defaultSort('sort_order')->filters([
            SelectFilter::make('type')->options([
                'banner' => 'Banner',
                'slider' => 'Slider',
            ])->label('Type'),
            SelectFilter::make('position')->options([
                'hero'         => 'Hero',
                'promo_top'    => 'Promo Top',
                'promo_bottom' => 'Promo Bottom',
                'sidebar'      => 'Sidebar',
                'popup'        => 'Popup',
            ]),
            TernaryFilter::make('is_active')->label('Active only'),
        ])->actions([
            ViewAction::make()->label('View'),
            EditAction::make(),

            // ── Convert type ─────────────────────────────────────
            Action::make('toggle_type')->label(fn(Banner $record) => $record->type === 'slider' ? 'Make Banner' : 'Make Slider')->color(fn(Banner $record) => $record->type === 'slider' ? 'gray' : 'info')->icon(fn(Banner $record) => $record->type === 'slider' ? 'heroicon-o-photo' : 'heroicon-o-squares-2x2')->requiresConfirmation()->modalHeading(fn(Banner $record) => $record->type === 'slider' ? 'Convert to Banner' : 'Convert to Slider')->modalDescription(fn(Banner $record) => $record->type === 'slider' ? 'This item will be removed from the slider carousel and displayed as a static banner instead.' : 'This item will join the rotating slider carousel for its position. Other banners at the same position will also need to be sliders for the carousel to work correctly.')->modalSubmitActionLabel('Yes, convert it')->action(function (Banner $record) {
                $newType = $record->type === 'slider' ? 'banner' : 'slider';
                $record->update(['type' => $newType]);
                $label = $newType === 'slider' ? 'Slider' : 'Banner';
                Notification::make()->success()->title("Converted to {$label}")->body("\"{$record->title}\" is now a {$label}.")->send();
            }),

            // ── Toggle active ─────────────────────────────────────
            Action::make('toggle_active')->label(fn(Banner $record) => $record->is_active ? 'Deactivate' : 'Activate')->color(fn(Banner $record) => $record->is_active ? 'danger' : 'success')->icon(fn(Banner $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')->action(function (Banner $record) {
                $record->update(['is_active' => !$record->is_active]);
                $state = $record->is_active ? 'activated' : 'deactivated';
                Notification::make()->success()->title("Banner {$state}!")->send();
            }),
            DeleteAction::make(),
        ])->bulkActions([
            BulkAction::make('bulk_make_slider')->label('Convert to Sliders')->color('info')->icon('heroicon-o-squares-2x2')->requiresConfirmation()->modalHeading('Convert selected to Sliders')->modalDescription('All selected items will become slider carousel items.')->action(fn($records) => $records->each->update(['type' => 'slider']))->deselectRecordsAfterCompletion(),
            BulkAction::make('bulk_make_banner')->label('Convert to Banners')->color('gray')->icon('heroicon-o-photo')->requiresConfirmation()->modalHeading('Convert selected to Banners')->modalDescription('All selected items will become static banners.')->action(fn($records) => $records->each->update(['type' => 'banner']))->deselectRecordsAfterCompletion(),
            DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'view'   => ViewBanner::route('/{record}'),
            'edit'   => EditBanner::route('/{record}/edit'),
        ];
    }
}
