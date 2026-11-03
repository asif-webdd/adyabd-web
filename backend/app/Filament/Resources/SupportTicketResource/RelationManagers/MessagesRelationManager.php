<?php

namespace App\Filament\Resources\SupportTicketResource\RelationManagers;

use App\Models\SupportTicket;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** The conversation of a ticket + the admin's reply box. */
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';
    protected static ?string $title = 'Conversation';
    protected static bool $isLazy = false;

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('message')->label('Your reply')->required()->rows(5)->maxLength(2000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->paginated(false)
            ->columns([
                TextColumn::make('sender_type')->label('From')->badge()
                    ->formatStateUsing(fn($state) => $state === 'admin' ? 'Support team' : 'Customer')
                    ->color(fn($state) => $state === 'admin' ? 'success' : 'info'),
                TextColumn::make('message')->wrap()->label('Message')->grow(),
                TextColumn::make('created_at')->label('Sent')->dateTime('d M Y, h:i A'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Reply to customer')->icon('heroicon-o-paper-airplane')->modalHeading('Reply to customer')
                    ->using(function (array $data, RelationManager $livewire): Model {
                        /** @var SupportTicket $ticket */
                        $ticket = $livewire->getOwnerRecord();
                        $msg = $ticket->addAdminReply($data['message'], Auth::guard('admin')->id());
                        Notification::make()->success()->title('Reply sent')->body('The customer was notified in the app' . ($ticket->user?->phone ? ' and by SMS.' : '.'))->send();
                        return $msg;
                    }),
            ]);
    }
}
