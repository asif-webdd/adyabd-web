<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use Filament\Forms\{Form, Components\TextInput, Components\Toggle, Components\Textarea};
use Filament\Tables\{Table, Columns\TextColumn, Columns\IconColumn, Actions\EditAction, Actions\DeleteAction};
use Filament\Resources\Resource;

class DepartmentResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'hr';
    protected static ?string $model = \App\Models\Department::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'HRM';
    protected static ?string $navigationLabel = 'Departments';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('name_bn'),
            Textarea::make('description'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('departments', ['ID' => 'id', 'Name' => 'name', 'Name (BN)' => 'name_bn', 'Active' => 'is_active'], [])])
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('employees_count')->counts('employees')->badge(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit'   => EditDepartment::route('/{record}/edit'),
        ];
    }
}
