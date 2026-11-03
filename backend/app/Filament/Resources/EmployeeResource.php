<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use App\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\FileUpload, Components\DatePicker};
use Filament\Tables\{Table, Columns\TextColumn, Columns\ImageColumn, Actions\EditAction, Actions\DeleteAction, Actions\ViewAction, Filters\SelectFilter};
use Filament\Resources\Resource;

class EmployeeResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'hr';
    protected static ?string $model = \App\Models\Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'HRM';
    protected static ?string $navigationLabel = 'Employees';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Personal Information')->schema([
                FileUpload::make('avatar')->image()->directory('employees')->avatar(),
                TextInput::make('name')->required(),
                TextInput::make('name_bn')->label('Name (Bangla)'),
                TextInput::make('email')->email()->unique(ignoreRecord: true),
                TextInput::make('phone')->required(),
                TextInput::make('emergency_phone')->label('Emergency Phone'),
                DatePicker::make('date_of_birth')->label('Date of Birth'),
                Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                Select::make('blood_group')->label('Blood Group')
                    ->options(array_combine(
                        ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
                        ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
                    )),
                TextInput::make('nid_number')->label('NID Number'),
            ])->columns(2),

            Section::make('Job Information')->schema([
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()->preload()->required(),
                Select::make('designation_id')
                    ->relationship('designation', 'title')
                    ->searchable()->preload()->required(),
                DatePicker::make('join_date')->label('Join Date')->required(),
                Select::make('employment_type')->label('Employment Type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'contract'  => 'Contract',
                        'intern'    => 'Intern',
                    ])
                    ->required(),
                Select::make('status')
                    ->options([
                        'active'     => 'Active',
                        'inactive'   => 'Inactive',
                        'resigned'   => 'Resigned',
                        'terminated' => 'Terminated',
                    ])
                    ->required(),
            ])->columns(2),

            Section::make('Salary & Payment')->schema([
                TextInput::make('basic_salary')->label('Basic Salary')->numeric()->prefix('BDT '),
                TextInput::make('house_rent')->label('House Rent')->numeric()->prefix('BDT '),
                TextInput::make('medical_allowance')->label('Medical Allowance')->numeric()->prefix('BDT '),
                TextInput::make('transport_allowance')->label('Transport Allowance')->numeric()->prefix('BDT '),
                TextInput::make('bkash_number')->label('bKash Number'),
                TextInput::make('bank_account')->label('Bank Account'),
                TextInput::make('bank_name')->label('Bank Name'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('employees', ['ID' => 'employee_id', 'Name' => 'name', 'Phone' => 'phone', 'Email' => 'email', 'Department' => 'department.name', 'Status' => 'status', 'Joined' => 'join_date', 'Basic salary' => 'basic_salary', 'bKash' => 'bkash_number'], ['department'])])
            ->columns([
                ImageColumn::make('avatar')->circular()->size(40),
                TextColumn::make('employee_id')->label('Employee ID')->searchable(),
                TextColumn::make('name')->searchable()->description(fn(\App\Models\Employee $record) => $record->phone),
                TextColumn::make('department.name')->label('Department')->badge(),
                TextColumn::make('designation.title')->label('Designation'),
                TextColumn::make('join_date')->label('Join Date')->date(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match($state) {
                        'active'     => 'Active',
                        'inactive'   => 'Inactive',
                        'resigned'   => 'Resigned',
                        'terminated' => 'Terminated',
                        default      => $state,
                    })
                    ->color(fn(string $state) => match($state) {
                        'active'                 => 'success',
                        'inactive'               => 'warning',
                        'resigned', 'terminated' => 'danger',
                        default                  => 'secondary',
                    }),
            ])
            ->filters([
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
                SelectFilter::make('status')->options([
                    'active'     => 'Active',
                    'inactive'   => 'Inactive',
                    'resigned'   => 'Resigned',
                    'terminated' => 'Terminated',
                ]),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view'   => ViewEmployee::route('/{record}'),
            'edit'   => EditEmployee::route('/{record}/edit'),
        ];
    }
}
