<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages\CreateLeave;
use App\Filament\Resources\LeaveResource\Pages\EditLeave;
use App\Filament\Resources\LeaveResource\Pages\ListLeaves;
use Filament\Forms\{Form, Components\Select, Components\TextInput, Components\DatePicker};
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Actions\EditAction, Actions\DeleteAction};
use Filament\Resources\Resource;

class LeaveResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'hr';
    protected static ?string $model = \App\Models\Leave::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'HRM';
    protected static ?string $navigationLabel = 'Leave Requests';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('employee_id')
                ->relationship('employee', 'name')
                ->searchable()->preload()->required(),
            Select::make('type')
                ->options([
                    'annual'   => 'Annual',
                    'sick'     => 'Sick',
                    'casual'   => 'Casual',
                    'maternity'=> 'Maternity',
                    'unpaid'   => 'Unpaid',
                ])->required(),
            DatePicker::make('from_date')->required(),
            DatePicker::make('to_date')->required(),
            TextInput::make('days')->numeric()->required(),
            TextInput::make('reason')->required(),
            Select::make('status')
                ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                ->required(),
            TextInput::make('admin_note'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('leaves', ['ID' => 'id', 'Employee' => 'employee.name', 'Type' => 'type', 'From' => 'from_date', 'To' => 'to_date', 'Days' => 'days', 'Status' => 'status', 'Reason' => 'reason', 'Admin note' => 'admin_note'], ['employee'])])
            ->columns([
                TextColumn::make('employee.name')->searchable(),
                BadgeColumn::make('type'),
                TextColumn::make('from_date')->date(),
                TextColumn::make('to_date')->date(),
                TextColumn::make('days'),
                BadgeColumn::make('status')
                    ->color(fn($state) => match($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'warning',
                    }),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLeaves::route('/'),
            'create' => CreateLeave::route('/create'),
            'edit'   => EditLeave::route('/{record}/edit'),
        ];
    }
}
