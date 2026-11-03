<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn, Columns\IconColumn, Filters\SelectFilter, Actions\Action};
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, Grid, IconEntry};
use Filament\Notifications\Notification;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';
    protected static ?string $title       = 'Reviews';
    protected static bool $isLazy         = false;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->product?->name),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->formatStateUsing(fn ($state) => "{$state}/5 stars")
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default     => 'danger',
                    }),

                TextColumn::make('title')
                    ->label('Title')
                    ->limit(30)
                    ->default('—'),

                TextColumn::make('review')
                    ->label('Review')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->review)
                    ->default('—'),

                IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('Approval Status')
                    ->options([
                        '1' => 'Approved',
                        '0' => 'Pending',
                    ]),
                SelectFilter::make('rating')
                    ->options([
                        '5' => '5 Stars',
                        '4' => '4 Stars',
                        '3' => '3 Stars',
                        '2' => '2 Stars',
                        '1' => '1 Star',
                    ]),
            ])
            ->actions([
                Action::make('view_review')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading('Review Details')
                    ->infolist(fn (Infolist $infolist, $record) =>
                        $infolist->record($record)->schema([
                            Section::make()->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('product.name')->label('Product'),
                                    TextEntry::make('rating')->label('Rating')
                                        ->formatStateUsing(fn ($state) => "{$state}/5 stars")
                                        ->badge()
                                        ->color(fn ($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),
                                    TextEntry::make('is_approved')->label('Approved')
                                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'success' : 'danger'),
                                    TextEntry::make('is_featured')->label('Featured')
                                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'warning' : 'gray'),
                                    TextEntry::make('created_at')->label('Submitted')->dateTime('d M Y, h:i A'),
                                ]),
                                TextEntry::make('title')->label('Review Title')->default('—'),
                                TextEntry::make('review')->label('Review Body')->default('—'),
                                TextEntry::make('comment')->label('Comment')->default('—'),
                                TextEntry::make('admin_reply')->label('Admin Reply')->default('No reply yet'),
                                TextEntry::make('admin_replied_at')->label('Replied At')
                                    ->dateTime('d M Y')->default('—'),
                            ]),
                        ])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('toggle_approve')
                    ->label(fn ($record) => $record->is_approved ? 'Unapprove' : 'Approve')
                    ->icon(fn ($record) => $record->is_approved ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_approved ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_approved' => ! $record->is_approved]);
                        $label = $record->is_approved ? 'approved' : 'unapproved';
                        Notification::make()->success()->title("Review {$label}")->send();
                    }),
            ])
            ->headerActions([])
            ->paginated([10, 25]);
    }
}
