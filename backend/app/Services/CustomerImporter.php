<?php

namespace App\Services;

use App\Models\Address;
use App\Models\District;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Bulk-import customers (e.g. from an old WordPress/WooCommerce shop) from a CSV file.
 *
 *  - phone is the login id, normalised to 01XXXXXXXXX (accepts 1XXXXXXXXX, 8801XXXXXXXXX, +8801XXXXXXXXX)
 *  - password: when the column is empty the customer's phone number is used (customer can change it later)
 *  - a phone that already exists is SKIPPED (never overwritten), duplicates inside the file are skipped
 *  - placeholder emails (@phone.user, @guest.…, @….local) are ignored, so no fake emails get stored
 */
class CustomerImporter
{
    /** Columns of the sample/template CSV, in order. */
    public const COLUMNS = [
        'name', 'phone', 'email', 'password', 'status', 'preferred_language',
        'total_orders', 'total_spent', 'wallet_balance', 'address', 'district',
        'registered_at', 'last_active_at',
    ];

    private const ALIASES = [
        'mobile' => 'phone', 'phone_number' => 'phone', 'full_name' => 'name', 'customer_name' => 'name',
        'orders' => 'total_orders', 'total_spend' => 'total_spent', 'date_registered' => 'registered_at',
        'last_active' => 'last_active_at', 'language' => 'preferred_language',
    ];

    /** Emails that are only placeholders created by the old shop / apps. */
    private const FAKE_EMAIL = '/@(phone\.user|[^@]*\.local|guest\.[^@]+|adyabd\.com)$/i';

    /**
     * @return array{created:int,would_create:int,skipped_existing:int,skipped_duplicate:int,invalid:int,issues:array<int,string>,dry_run:bool}
     */
    public function import(string $path, bool $dryRun = false, bool $withAddress = true): array
    {
        $report = ['created' => 0, 'would_create' => 0, 'skipped_existing' => 0, 'skipped_duplicate' => 0, 'invalid' => 0, 'issues' => [], 'dry_run' => $dryRun];

        $fh = fopen($path, 'r');
        if (!$fh) {
            throw new \RuntimeException('Could not open the uploaded file.');
        }

        $first = fgets($fh);
        if ($first === false) {
            fclose($fh);
            throw new \RuntimeException('The file is empty.');
        }
        $first  = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delim  = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn($h) => $this->headerKey($h), str_getcsv($first, $delim));
        if (!in_array('phone', $header, true)) {
            fclose($fh);
            throw new \RuntimeException('The file has no "phone" column. Download the sample CSV and use its columns.');
        }

        $seen = [];
        $line = 1;
        while (($cells = fgetcsv($fh, 0, $delim)) !== false) {
            $line++;
            if ($cells === [null] || count(array_filter($cells, fn($c) => trim((string) $c) !== '')) === 0) {
                continue; // blank line
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
            }

            $phone = self::normalizePhone($row['phone'] ?? '');
            if (!$phone) {
                $report['invalid']++;
                $this->issue($report, "Row {$line}: invalid or missing phone (\"" . ($row['phone'] ?? '') . '")');
                continue;
            }
            if (isset($seen[$phone])) {
                $report['skipped_duplicate']++;
                $this->issue($report, "Row {$line}: {$phone} appears twice in the file, skipped");
                continue;
            }
            $seen[$phone] = true;

            if (User::withTrashed()->where('phone', $phone)->exists()) {
                $report['skipped_existing']++;
                continue;
            }

            $name = Str::limit(trim(preg_replace('/^(নাম|name)\s*[:：]\s*/iu', '', $row['name'] ?? '')) ?: $phone, 250, '');

            if ($dryRun) {
                $report['would_create']++;
                continue;
            }

            DB::transaction(function () use ($row, $phone, $name, $withAddress, &$report) {
                $user = new User();
                $user->fill([
                    'name'               => $name,
                    'phone'              => $phone,
                    'email'              => $this->cleanEmail($row['email'] ?? ''),
                    // plain text on purpose: the model's "hashed" cast hashes it
                    'password'           => ($row['password'] ?? '') !== '' ? $row['password'] : $phone,
                    'status'             => in_array($row['status'] ?? '', ['active', 'inactive', 'banned'], true) ? $row['status'] : 'active',
                    'preferred_language' => in_array($row['preferred_language'] ?? '', ['bn', 'en'], true) ? $row['preferred_language'] : 'bn',
                    'total_orders'       => max(0, (int) ($row['total_orders'] ?? 0)),
                    'total_spent'        => max(0, (float) ($row['total_spent'] ?? 0)),
                    'wallet_balance'     => max(0, (float) ($row['wallet_balance'] ?? 0)),
                    'referral_code'      => $this->referralCode($phone),
                ]);
                if ($d = $this->date($row['registered_at'] ?? '')) {
                    $user->created_at = $d;
                }
                if ($d = $this->date($row['last_active_at'] ?? '')) {
                    $user->last_login_at = $d;
                }
                $user->save();

                if ($withAddress && ($row['address'] ?? '') !== '') {
                    $district = $this->district($row['district'] ?? '');
                    Address::create([
                        'user_id'      => $user->id,
                        'name'         => $name,
                        'phone'        => $phone,
                        'type'         => 'home',
                        'address_line' => $row['address'],
                        'division_id'  => $district?->division_id,
                        'district_id'  => $district?->id,
                        'is_default'   => true,
                    ]);
                }
                $report['created']++;
            });
        }
        fclose($fh);

        return $report;
    }

    /** 01XXXXXXXXX or null when the value is not a Bangladeshi mobile number. */
    public static function normalizePhone(?string $v): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $v);
        if (str_starts_with($d, '880') && strlen($d) === 13) {
            $d = '0' . substr($d, 3);
        } elseif (strlen($d) === 10 && $d[0] === '1') {
            $d = '0' . $d;        // Excel dropped the leading zero
        }
        return preg_match('/^01[3-9]\d{8}$/', $d) ? $d : null;
    }

    public static function sampleCsv(): string
    {
        $rows = [
            self::COLUMNS,
            ['রফিক', '01838992065', '', '', 'active', 'bn', '1', '698', '0', 'শরীয়তপুর সদর', 'Shariatpur', '2026-11-08 03:58:25', '2026-11-08 03:58:25'],
            ['Mehedi Miyazi', '01745141526', 'mehedi@example.com', 'MyOwn@123', 'active', 'en', '2', '1400', '0', 'Chandpur Sadar', 'Chandpur', '2026-08-12 10:55:36', ''],
        ];
        $out = "\xEF\xBB\xBF";
        foreach ($rows as $r) {
            $out .= implode(',', array_map(fn($c) => '"' . str_replace('"', '""', (string) $c) . '"', $r)) . "\r\n";
        }
        return $out;
    }

    private function headerKey(?string $h): string
    {
        $k = Str::snake(Str::lower(trim((string) $h)));
        $k = str_replace([' ', '-', '/'], '_', $k);
        return self::ALIASES[$k] ?? $k;
    }

    private function cleanEmail(string $e): ?string
    {
        $e = Str::lower(trim($e));
        if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL) || preg_match(self::FAKE_EMAIL, $e)) {
            return null;
        }
        return User::withTrashed()->where('email', $e)->exists() ? null : $e;
    }

    private function referralCode(string $phone): string
    {
        do {
            $code = strtoupper(substr(md5($phone . Str::random(8)), 0, 8));
        } while (User::withTrashed()->where('referral_code', $code)->exists());
        return $code;
    }

    private function date(string $v): ?Carbon
    {
        if ($v === '') return null;
        try { return Carbon::parse($v); } catch (\Throwable) { return null; }
    }

    private function district(string $name): ?District
    {
        $name = trim($name);
        if ($name === '') return null;
        return District::whereRaw('LOWER(name) = ?', [Str::lower($name)])->orWhere('name_bn', $name)->first();
    }

    private function issue(array &$report, string $msg): void
    {
        if (count($report['issues']) < 25) {
            $report['issues'][] = $msg;
        }
    }
}