<?php

namespace App\Filament\Resources;

use App\Models\AutoBlockRule;
use Filament\Forms\{Form,
    Components\Section,
    Components\TextInput,
    Components\Select,
    Components\Toggle,
    Components\Textarea
};
use Filament\Tables\{Table, Columns\TextColumn, Columns\IconColumn, Actions\EditAction, Actions\DeleteAction};
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;

class AutoBlockRuleResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'customers';
    protected static ?string $model = AutoBlockRule::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Auto Block Rules';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('name')->required(),
                Select::make('trigger')
                    ->options([
                        'cancel_order'    => 'Order Cancelled',
                        'failed_delivery' => 'Failed Delivery',
                        'no_answer'       => 'No Answer',
                    ])
                    ->required(),
                TextInput::make('threshold')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Number of times before blocking'),
                TextInput::make('within_days')
                    ->numeric()
                    ->nullable()
                    ->helperText('Within how many days (empty = all time)'),
                Select::make('block_duration')
                    ->options([
                        '7 days'    => '7 Days',
                        '30 days'   => '30 Days',
                        '90 days'   => '90 Days',
                        'permanent' => 'Permanent',
                    ])
                    ->required()
                    ->default('permanent'),
                Textarea::make('reason_template')
                    ->required()
                    ->rows(2)
                    ->placeholder('Due to {count} times order cancellation...'),
                Toggle::make('block_ip_also')->default(true),
                Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            BadgeColumn::make('trigger')
                ->formatStateUsing(fn(string $state) => match ($state) {
                    'cancel_order' => 'Cancelled',
                    'failed_delivery' => 'Failed',
                    'no_answer' => 'No Answer',
                    default => $state,
                })
                ->colors([
                    'danger'  => 'cancel_order',
                    'warning' => 'failed_delivery',
                    'primary' => 'no_answer',
                ]),
            TextColumn::make('threshold')->formatStateUsing(fn(string $state, $record) => "{$state} times" . ($record->within_days ? " ({$record->within_days} days)" : "")),
            TextColumn::make('block_duration')->formatStateUsing(fn(string $state) => match ($state) {
                'permanent' => 'Permanent',
                '7 days' => '7 Days',
                '30 days' => '30 Days',
                '90 days' => '90 Days',
                default => $state,
            })->badge()->color(fn(string $state) => $state === 'permanent' ? 'danger' : 'warning'), IconColumn::make('block_ip_also')->boolean(),
            IconColumn::make('is_active')->boolean(),
        ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\AutoBlockRuleResource\Pages\ListAutoBlockRules::route('/'),
            'create' => \App\Filament\Resources\AutoBlockRuleResource\Pages\CreateAutoBlockRule::route('/create'),
            'edit'   => \App\Filament\Resources\AutoBlockRuleResource\Pages\EditAutoBlockRule::route('/{record}/edit'),
        ];
    }
}
