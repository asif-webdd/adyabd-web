<?php

namespace App\Filament\Support;

use App\Services\CustomerImporter;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

/**
 * "Import CSV" + "Sample CSV" buttons for the Users (customers) table.
 */
class CustomerImport
{
    public static function actions(): array
    {
        return [self::sample(), self::import()];
    }

    public static function sample(): Action
    {
        return Action::make('customers_sample_csv')
            ->label('Sample CSV')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(fn() => response()->streamDownload(
                fn() => print(CustomerImporter::sampleCsv()),
                'customers-import-sample.csv',
                ['Content-Type' => 'text/csv; charset=UTF-8']
            ));
    }

    public static function import(): Action
    {
        return Action::make('customers_import_csv')
            ->label('Import CSV')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('Import customers from CSV')
            ->modalDescription('Upload a CSV with the columns of the sample file. Phone is the login id; the password is the phone number unless the file has its own.')
            ->modalSubmitActionLabel('Import')
            ->form([
                Placeholder::make('help')->label('')->content(new HtmlString(
                    '<div class="text-sm" style="line-height:1.6">'
                    . '<b>Columns:</b> ' . e(implode(', ', CustomerImporter::COLUMNS)) . '<br>'
                    . 'Only <b>phone</b> is required. Existing phone numbers are skipped (never overwritten).'
                    . '</div>'
                )),
                FileUpload::make('file')
                    ->label('CSV file')
                    ->required()
                    ->storeFiles(false)
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', '.csv'])
                    ->maxSize(5120),
                Checkbox::make('with_address')->label('Also create a default address from the "address" / "district" columns')->default(true),
                Checkbox::make('dry_run')->label('Check only: show what would happen, save nothing')->default(false),
            ])
            ->action(function (array $data) {
                $file = $data['file'] ?? null;
                if (is_array($file)) {
                    $file = reset($file);
                }
                $path = is_object($file) && method_exists($file, 'getRealPath') ? $file->getRealPath() : null;
                if (!$path) {
                    Notification::make()->danger()->title('Could not read the uploaded file')->send();
                    return;
                }

                set_time_limit(300);
                try {
                    $r = (new CustomerImporter())->import($path, (bool) ($data['dry_run'] ?? false), (bool) ($data['with_address'] ?? true));
                } catch (\Throwable $e) {
                    Notification::make()->danger()->title('Import failed')->body($e->getMessage())->persistent()->send();
                    return;
                }

                $lines = [];
                $lines[] = $r['dry_run'] ? "Would create: {$r['would_create']}" : "Created: {$r['created']}";
                $lines[] = "Already existed (skipped): {$r['skipped_existing']}";
                if ($r['skipped_duplicate']) $lines[] = "Duplicate in file (skipped): {$r['skipped_duplicate']}";
                if ($r['invalid'])           $lines[] = "Invalid / no phone (skipped): {$r['invalid']}";
                $body = implode("\n", $lines);
                if ($r['issues']) {
                    $body .= "\n\n" . implode("\n", array_slice($r['issues'], 0, 10));
                }

                $n = Notification::make()->title($r['dry_run'] ? 'Check finished (nothing saved)' : 'Import finished')->body(new HtmlString(nl2br(e($body))))->persistent();
                ($r['invalid'] || $r['skipped_duplicate'] ? $n->warning() : $n->success())->send();
            });
    }
}