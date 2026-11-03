<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages\EditSupportTicket;
use App\Filament\Resources\SupportTicketResource\Pages\ListSupportTickets;
use Filament\Forms\{Form, Components\Select, Components\TextInput};
use App\Filament\Resources\SupportTicketResource\RelationManagers\MessagesRelationManager;
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Actions\EditAction, Actions\DeleteAction};
use Filament\Resources\Resource;

class SupportTicketResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'support';
    protected static ?string $model = \App\Models\SupportTicket::class;
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Support Tickets';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('subject')->disabled()->dehydrated(false)->columnSpanFull(),
            Select::make('status')
                ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'])
                ->required(),
            Select::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'])
                ->required(),
            Select::make('assigned_to')
                ->relationship('assignedTo', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function getRelations(): array
    {
        return [MessagesRelationManager::class];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('support-tickets', ['ID' => 'id', 'Ticket' => 'ticket_number', 'Date' => 'created_at', 'Customer' => 'user.name', 'Phone' => 'user.phone', 'Subject' => 'subject', 'Category' => 'category', 'Priority' => 'priority', 'Status' => 'status'], ['user'])])
            ->columns([
                TextColumn::make('ticket_number')->copyable(),
                TextColumn::make('user.name')->description(fn($r) => $r->user?->phone),
                TextColumn::make('subject')->limit(40),
                BadgeColumn::make('category'),
                BadgeColumn::make('priority')
                    ->color(fn($state) => match($state) {
                        'urgent' => 'danger',
                        'high'   => 'warning',
                        'medium' => 'info',
                        default  => 'secondary',
                    }),
                BadgeColumn::make('status')
                    ->color(fn($state) => match($state) {
                        'open'        => 'danger',
                        'in_progress' => 'warning',
                        'resolved', 'closed' => 'success',
                        default => 'secondary',
                    }),
                TextColumn::make('created_at')->date(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'edit'  => EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
