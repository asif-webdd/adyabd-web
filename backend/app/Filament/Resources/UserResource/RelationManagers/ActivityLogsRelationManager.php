<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\ActivityLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, Grid};
use Illuminate\Database\Eloquent\Builder;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string  $relationship = 'activityLogs';
    protected static ?string $title        = 'Activity Log';
    protected static ?string $icon         = 'heroicon-o-clipboard-document-list';
    protected static bool    $isLazy       = false;

    // ── Custom query — show logs where user is subject OR causer ──────

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('description')->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('created_at')->label('Time')->dateTime('d M Y, h:i A')->sortable()->width('160px'),
            TextColumn::make('event')->label('Event')->badge()->color(fn(?string $state) => match ($state) {
                'created'  => 'success',
                'updated'  => 'info',
                'deleted'  => 'danger',
                'restored' => 'warning',
                'login'    => 'success',
                'logout'   => 'gray',
                default    => 'gray',
            })->icon(fn(?string $state) => match ($state) {
                'created'  => 'heroicon-o-plus-circle',
                'updated'  => 'heroicon-o-pencil-square',
                'deleted'  => 'heroicon-o-trash',
                'restored' => 'heroicon-o-arrow-uturn-left',
                'login'    => 'heroicon-o-arrow-right-on-rectangle',
                'logout'   => 'heroicon-o-arrow-left-on-rectangle',
                default    => 'heroicon-o-ellipsis-horizontal-circle',
            })->formatStateUsing(fn(?string $state) => ucfirst($state ?? '—')),
            TextColumn::make('description')->label('Description')->wrap()->searchable(),
            TextColumn::make('log_name')->label('Module')->badge()->color('gray')->formatStateUsing(fn(?string $state) => ucfirst($state ?? '—')),
            TextColumn::make('causer_name')->label('Done By')->placeholder('System')->icon('heroicon-m-user-circle'),
            TextColumn::make('ip_address')->label('IP Address')->copyable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('event')->options([
                'created' => 'Created',
                'updated' => 'Updated',
                'deleted' => 'Deleted',
                'login'   => 'Login',
                'logout'  => 'Logout',
            ])->placeholder('All Events'),
            SelectFilter::make('log_name')->label('Module')->options(fn() => ActivityLog::distinct()->pluck('log_name', 'log_name')->toArray())->placeholder('All Modules'),
            Filter::make('today')->label('Today only')->query(fn(Builder $q) => $q->whereDate('created_at', today())),
        ])->actions([
            Action::make('view_detail')->label('Details')->icon('heroicon-o-eye')->color('gray')->slideOver()->modalWidth('2xl')->modalHeading(fn(ActivityLog $record) => ucfirst($record->event ?? 'Activity') . ' — ' . ucfirst($record->log_name))->infolist(function (Infolist $infolist) {
                $log = $infolist->getRecord();
                return $infolist->record($log)->schema([
                    Section::make('Event Details')->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('event')->label('Event')->badge()->color(fn($state) => match ($state) {
                                'created' => 'success',
                                'updated' => 'info',
                                'deleted' => 'danger',
                                'login'   => 'success',
                                'logout'  => 'gray',
                                default   => 'gray',
                            })->formatStateUsing(fn($state) => ucfirst($state ?? '—')),

                            TextEntry::make('log_name')->label('Module')->badge()->color('gray')->formatStateUsing(fn($state) => ucfirst($state ?? '—')),
                            TextEntry::make('created_at')->label('Time')->dateTime('d M Y, h:i A'),
                        ]),
                        TextEntry::make('description')->label('Description')->columnSpanFull(),
                    ]),

                    Section::make('Performed By')->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('causer_name')->label('Name')->placeholder('System'),
                            TextEntry::make('ip_address')->label('IP Address')->copyable()->placeholder('—'),
                            TextEntry::make('user_agent')->label('User Agent')->placeholder('—')->limit(60),
                        ]),
                    ]),

                    Section::make('Changes')->schema([
                        TextEntry::make('old_values')->label('Before')->getStateUsing(function (ActivityLog $record) {
                            $old = $record->properties['old'] ?? null;
                            if (!$old) return '—';
                            return collect($old)->map(fn($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))->implode("\n");
                        })->placeholder('—'),

                        TextEntry::make('new_values')->label('After')->getStateUsing(function (ActivityLog $record) {
                            $new = $record->properties['attributes'] ?? null;
                            if (!$new) return '—';
                            return collect($new)->map(fn($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))->implode("\n");
                        })->placeholder('—'),
                    ])->columns(2)->visible(fn() => !empty($infolist->getRecord()->properties)),
                ]);
            })->modalSubmitAction(false)->modalCancelActionLabel('Close'),
        ])->paginated([15, 25, 50])->poll('60s');
    }

    protected function getTableQuery(): Builder
    {
        $user = $this->getOwnerRecord();
        $userClass = get_class($user);

        return ActivityLog::query()->where(function (Builder $q) use ($user, $userClass) {
            $q->where(function (Builder $q) use ($user, $userClass) {
                $q->where('subject_type', $userClass)->where('subject_id', $user->id);
            })->orWhere(function (Builder $q) use ($user, $userClass) {
                $q->where('causer_type', $userClass)->where('causer_id', $user->id);
            });
        })->orderByDesc('created_at');
    }
}
