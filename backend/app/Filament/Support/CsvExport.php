<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

/**
 * "Export CSV" button for any Filament table. It exports exactly what the admin currently sees
 * (search + filters + sort applied), streams the file (works for very large tables) and writes a UTF-8 BOM
 * so Excel shows Bangla text correctly.
 *
 *   CsvExport::make('orders', ['Order #' => 'order_number', 'Customer' => 'user.name', 'Total' => fn($o) => $o->total_amount], ['user'])
 */
class CsvExport
{
    public static function make(string $name, array $columns, array $with = []): Action
    {
        return Action::make('export_csv')
            ->label('Export CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function ($livewire) use ($name, $columns, $with) {
                $query = $livewire->getFilteredSortedTableQuery();
                if ($with) {
                    $query->with($with);
                }
                if (empty($query->getQuery()->orders)) {
                    $query->orderBy($query->getModel()->getQualifiedKeyName());
                }
                $count = (clone $query)->count();
                if ($count > 50000) {
                    Notification::make()->warning()->title('Too many rows to export at once')
                        ->body("{$count} rows match. Narrow the list with filters (max 50,000) and try again.")->send();
                    return null;
                }

                $file = $name . '-' . now()->format('Y-m-d_His') . '.csv';
                return response()->streamDownload(function () use ($query, $columns) {
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");
                    fputcsv($out, array_keys($columns));
                    foreach ($query->lazy(500) as $row) {
                        fputcsv($out, array_map(fn($col) => self::cell($row, $col), array_values($columns)));
                    }
                    fclose($out);
                }, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
            });
    }

    private static function cell($row, $col): string
    {
        $v = $col instanceof \Closure ? $col($row) : data_get($row, $col);
        if ($v instanceof \DateTimeInterface) {
            $v = $v->format('Y-m-d H:i:s');
        } elseif (is_bool($v)) {
            $v = $v ? 'Yes' : 'No';
        } elseif (is_array($v)) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        $v = (string) ($v ?? '');
        // CSV/Excel formula injection guard (customer-supplied text like "=HYPERLINK(...)")
        return $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false && !is_numeric($v) ? "'" . $v : $v;
    }
}
