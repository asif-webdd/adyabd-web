<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use App\Services\PayrollService;
use Filament\Actions\{Action, CreateAction};
use Filament\Forms\Components\{Select, TextInput};
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        $prev = now()->subMonthNoOverflow();
        return [
            Action::make('generate')->label('Generate payroll')->icon('heroicon-o-calculator')->color('success')
                ->modalHeading('Generate payroll for a month')
                ->modalDescription('Creates one row per ACTIVE employee from salary, attendance and approved leave (Fridays are off). Employees that already have a row for that month are skipped. Nothing is paid automatically.')
                ->form([
                    Select::make('month')->options(array_combine(range(1, 12), ['January','February','March','April','May','June','July','August','September','October','November','December']))
                        ->default($prev->month)->required(),
                    TextInput::make('year')->numeric()->default($prev->year)->required(),
                ])
                ->action(function (array $data) {
                    $r = app(PayrollService::class)->generate((int) $data['month'], (int) $data['year']);
                    Notification::make()->success()->title("Payroll generated: {$r['created']} new, {$r['skipped']} already existed")->send();
                }),
            CreateAction::make(),
        ];
    }
}
