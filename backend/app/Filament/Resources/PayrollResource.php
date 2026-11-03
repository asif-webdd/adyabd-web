<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages\CreatePayroll;
use App\Filament\Resources\PayrollResource\Pages\EditPayroll;
use App\Filament\Resources\PayrollResource\Pages\ListPayrolls;
use Filament\Forms\{Form, Components\Select, Components\TextInput};
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Actions\EditAction, Actions\DeleteAction};
use Filament\Resources\Resource;

class PayrollResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'hr';
    protected static ?string $model = \App\Models\Payroll::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'HRM';
    protected static ?string $navigationLabel = 'Payroll';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('employee_id')
                ->relationship('employee', 'name')
                ->searchable()->preload()->required(),
            Select::make('month')
                ->options(array_combine(
                    range(1, 12),
                    ['January','February','March','April','May','June','July','August','September','October','November','December']
                ))->required(),
            TextInput::make('year')->numeric()->default(date('Y'))->required(),
            TextInput::make('basic_salary')->numeric()->prefix('BDT '),
            TextInput::make('house_rent')->numeric()->prefix('BDT '),
            TextInput::make('medical_allowance')->numeric()->prefix('BDT '),
            TextInput::make('transport_allowance')->numeric()->prefix('BDT '),
            TextInput::make('bonus')->numeric()->prefix('BDT '),
            TextInput::make('gross_salary')->numeric()->prefix('BDT '),
            TextInput::make('net_salary')->numeric()->required()->prefix('BDT '),
            Select::make('payment_method')
                ->options(['bkash' => 'bKash', 'bank' => 'Bank', 'cash' => 'Cash'])
                ->required(),
            Select::make('status')
                ->options(['pending' => 'Pending', 'paid' => 'Paid'])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('payroll', ['Employee' => 'employee.name', 'Month' => 'month', 'Year' => 'year', 'Gross' => 'gross_salary', 'Deductions' => 'other_deduction', 'Net' => 'net_salary', 'Present' => 'present_days', 'Absent' => 'absent_days', 'Leave' => 'leave_days', 'Method' => 'payment_method', 'Status' => 'status', 'Paid at' => 'paid_at'], ['employee'])])
            ->columns([
                TextColumn::make('employee.name')->searchable(),
                TextColumn::make('month')->formatStateUsing(fn($state) => $months[((int) $state) - 1] ?? $state),
                TextColumn::make('year'),
                TextColumn::make('net_salary')->money('BDT'),
                BadgeColumn::make('status')
                    ->color(fn($state) => $state === 'paid' ? 'success' : 'warning'),
                TextColumn::make('paid_at')->date()->placeholder('Pending'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('mark_paid')->label('Mark paid')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn($record) => $record->status !== 'paid')->requiresConfirmation()
                    ->action(fn($record) => $record->update(['status' => 'paid', 'paid_at' => now()])),
                EditAction::make(), DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'edit'   => EditPayroll::route('/{record}/edit'),
        ];
    }
}
