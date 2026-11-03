<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages\CreateAttendance;
use App\Filament\Resources\AttendanceResource\Pages\EditAttendance;
use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Models\Attendance;
use Filament\Forms\{Form, Components\Select, Components\TextInput, Components\DatePicker};
use Filament\Tables\{Table, Columns\TextColumn, Actions\EditAction, Actions\DeleteAction};
use Filament\Resources\Resource;

class AttendanceResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'hr';
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'HRM';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('employee_id')
                ->relationship('employee', 'name')
                ->searchable()
                ->preload()
                ->required(),
            DatePicker::make('date')->required(),
            TextInput::make('check_in')->label('Check In'),
            TextInput::make('check_out')->label('Check Out'),
            Select::make('status')
                ->options([
                    'present'  => 'Present',
                    'absent'   => 'Absent',
                    'late'     => 'Late',
                    'half_day' => 'Half Day',
                    'leave'    => 'On Leave',
                ])
                ->required(),
            TextInput::make('note')->label('Note'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('attendance', ['ID' => 'id', 'Employee' => 'employee.name', 'Date' => 'date', 'Check in' => 'check_in', 'Check out' => 'check_out', 'Hours' => 'working_hours', 'Status' => 'status', 'Note' => 'note'], ['employee'])])
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_in')->label('Check In'),
                TextColumn::make('check_out')->label('Check Out'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match($state) {
                        'present'  => 'Present',
                        'absent'   => 'Absent',
                        'late'     => 'Late',
                        'half_day' => 'Half Day',
                        'leave'    => 'On Leave',
                        default    => $state,
                    })
                    ->color(fn(string $state) => match($state) {
                        'present'            => 'success',
                        'absent'             => 'danger',
                        'late', 'half_day'   => 'warning',
                        default              => 'secondary',
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAttendances::route('/'),
            'create' => CreateAttendance::route('/create'),
            'edit'   => EditAttendance::route('/{record}/edit'),
        ];
    }
}
