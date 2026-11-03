<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use Filament\Forms\Components\{Select, Textarea, TextInput};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\{Action, DeleteAction, EditAction};
use Illuminate\Support\Facades\{Auth, Mail};
use Filament\Tables\Columns\{BadgeColumn, TextColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Messages sent through the public "Contact us" form (POST /api/v1/contact). */
class ContactMessageResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'support';
    protected static ?string $model = ContactMessage::class;
    protected static ?string $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Contact Messages';
    protected static ?int    $navigationSort  = 20;

    public static function getNavigationBadge(): ?string
    {
        $n = ContactMessage::where('status', 'open')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->disabled(),
            TextInput::make('phone')->disabled(),
            TextInput::make('email')->disabled(),
            TextInput::make('order_id')->label('Order number')->disabled(),
            TextInput::make('subject')->disabled()->columnSpanFull(),
            Textarea::make('message')->disabled()->rows(6)->columnSpanFull(),
            Select::make('status')->options([
                'open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved',
            ])->required(),
            Textarea::make('reply')->label('Reply sent to customer')->disabled()->dehydrated(false)->rows(3)->columnSpanFull()
                ->helperText('Use the "Reply" button in the list to answer the customer by SMS / email.'),
            Textarea::make('admin_note')->label('Internal note')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('contact-messages', ['ID' => 'id', 'Date' => 'created_at', 'Name' => 'name', 'Phone' => 'phone', 'Email' => 'email', 'Subject' => 'subject', 'Message' => 'message', 'Status' => 'status', 'Reply' => 'reply', 'Replied at' => 'replied_at'], [])])
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('phone')->searchable()->copyable(),
                TextColumn::make('subject')->limit(40)->searchable(),
                TextColumn::make('order_id')->label('Order')->toggleable(),
                BadgeColumn::make('status')->colors([
                    'warning' => 'open', 'info' => 'in_progress', 'success' => 'resolved',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved',
                ]),
            ])
            ->actions([
                Action::make('reply')->label('Reply')->icon('heroicon-o-paper-airplane')->color('success')
                    ->modalHeading(fn(ContactMessage $r) => 'Reply to ' . $r->name)
                    ->modalDescription(fn(ContactMessage $r) => $r->message)
                    ->form(fn(ContactMessage $r) => [
                        Select::make('channel')->label('Send by')->required()->default($r->email ? 'both' : 'sms')
                            ->options(array_filter([
                                'sms'   => 'SMS to ' . $r->phone,
                                'email' => $r->email ? 'Email to ' . $r->email : null,
                                'both'  => $r->email ? 'SMS + Email' : null,
                            ])),
                        Textarea::make('text')->label('Message')->required()->rows(5)->maxLength(900)
                            ->helperText('Keep SMS short (about 160 characters per SMS part).'),
                    ])
                    ->action(function (ContactMessage $r, array $data) {
                        $sent = static::sendReply($r, $data['channel'], $data['text']);
                        if ($sent) {
                            Notification::make()->success()->title('Reply sent')->body('Sent via: ' . implode(', ', $sent))->send();
                        } else {
                            Notification::make()->danger()->title('Reply NOT sent')
                                ->body('Neither SMS nor email could be delivered. Check Settings → SMS / SMTP, then try again.')->send();
                        }
                    }),
                EditAction::make()->label('View'),
                DeleteAction::make(),
            ]);
    }

    /** Sends the reply by SMS and/or email; stores it and marks the message resolved only if something was delivered. */
    public static function sendReply(ContactMessage $r, string $channel, string $text): array
    {
        $sent = [];
        if (in_array($channel, ['sms', 'both'], true) && $r->phone) {
            try {
                if (app(\App\Services\SmsService::class)->sendTemplate('contact_reply', $r->phone, ['reply' => $text], $r, '{shop_name}: {reply}', 'sms_on_contact_reply')) {
                    $sent[] = 'SMS';
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if (in_array($channel, ['email', 'both'], true) && $r->email) {
            try {
                Mail::raw($text . "\n\n— " . (setting('shop_name') ?: config('app.name')), function ($m) use ($r) {
                    $m->to($r->email, $r->name)->subject('Re: ' . $r->subject);
                });
                $sent[] = 'Email';
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if ($sent) {
            $r->update([
                'reply' => $text, 'reply_channel' => count($sent) > 1 ? 'both' : strtolower($sent[0]),
                'replied_at' => now(), 'replied_by' => Auth::guard('admin')->id(), 'status' => 'resolved',
            ]);
        }
        return $sent;
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactMessages::route('/')];
    }
}
