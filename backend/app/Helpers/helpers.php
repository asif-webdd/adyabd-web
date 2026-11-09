<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, fn() => Setting::get($key, $default));
    }
}

if (!function_exists('uploads_on_r2')) {
    /** True when the "public" disk is Cloudflare R2 / S3 instead of the local storage folder. */
    function uploads_on_r2(): bool
    {
        return config('filesystems.disks.public.driver') === 's3';
    }
}

if (!function_exists('storage_url')) {
    /**
     * Public URL of an uploaded file. Works for the local disk (/storage/…), for Cloudflare R2 (AWS_URL/…)
     * and for values that already are full http(s) URLs.
     */
    function storage_url(?string $path): ?string
    {
        if ($path === null || $path === '') return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) return $path;
        $path = ltrim($path, '/');
        if (uploads_on_r2()) {
            return rtrim((string) config('filesystems.disks.public.url'), '/') . '/' . $path;
        }
        return asset('storage/' . $path);
    }
}

if (!function_exists('shop_logo_src')) {
    /**
     * Store logo (Admin → Appearance → Store Logo) as something an <img> can show on a printed
     * invoice / PDF: an inline data-URI for an uploaded file (works offline, with or without the
     * storage symlink), the URL itself for an http(s) logo, or null when no logo is set.
     */
    function shop_logo_src(): ?string
    {
        $path = (string) \App\Models\Setting::get('shop_logo', '');
        if (str_starts_with($path, '[')) {
            $path = (string) (collect(json_decode($path, true) ?: [])->first() ?? '');
        }
        if ($path === '') return null;
        if (str_starts_with($path, 'http')) return $path;

        if (uploads_on_r2()) return storage_url($path);

        $file = storage_path('app/public/' . ltrim($path, '/'));
        if (!is_file($file) || filesize($file) > 3 * 1024 * 1024) {
            return storage_url($path);
        }
        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
            default => 'image/jpeg',
        };
        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($file));
    }
}

if (!function_exists('shop_display_name')) {
    function shop_display_name(): string
    {
        return (string) (\App\Models\Setting::get('shop_name') ?: config('app.name', 'Shop'));
    }
}

if (!function_exists('setting_set')) {
    function setting_set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::set($key, $value, $group);
        Cache::forget("setting_{$key}");
    }
}

if (!function_exists('format_price')) {
    function format_price(float $amount, bool $bn = true): string
    {
        $f = 'BDT ' . number_format($amount, 0);
        if (!$bn) return $f;
        return str_replace(['0','1','2','3','4','5','6','7','8','9'],['0','1','2','3','4','5','6','7','8','9'], $f);
    }
}

if (!function_exists('bd_phone')) {
    function bd_phone(string $phone): string
    {
        $p = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($p, '880')) return $p;
        if (str_starts_with($p, '0'))  return '880' . substr($p, 1);
        return '880' . $p;
    }
}


if (!function_exists('variant_label')) {
    /**
     * Human label for variant options. Handles both formats:
     *   [{"attribute":"Color","value":"Black"}, ...]  (current)
     *   {"Color":"Black","Size":"M"}                  (legacy)
     * and JSON strings. Returns "" when there is nothing to show.
     */
    function variant_label($options, string $sep = ', '): string
    {
        if (is_string($options)) {
            $options = json_decode($options, true);
        }
        if (!is_array($options) || empty($options)) {
            return '';
        }

        $parts = [];
        foreach ($options as $key => $val) {
            if (is_array($val)) {
                $name  = $val['attribute'] ?? $val['name'] ?? $val['key'] ?? null;
                $value = $val['value'] ?? null;
                if (is_array($value)) { $value = implode('/', array_map('strval', $value)); }
                $parts[] = $name !== null && $name !== '' ? "{$name}: {$value}" : (string) ($value ?? '');
            } else {
                $parts[] = is_int($key) ? (string) $val : "{$key}: {$val}";
            }
        }

        return implode($sep, array_filter($parts, fn($x) => $x !== ''));
    }

    
    function sanitize_rich_html(?string $html): ?string
    {
        if ($html === null) return null;
        $html = trim($html);
        if ($html === '' || !preg_match('/<[a-z!\/]/i', $html)) return $html;

        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
        $allowed = '<p><br><strong><b><em><i><u><s><strike><a><h2><h3><h4><ul><ol><li><blockquote><code><pre><hr>';
        $html = strip_tags($html, $allowed);

        $prev = libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="wrap">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($doc);
        foreach ($xpath->query('//*[@id="wrap"]//*') as $el) {
            foreach (iterator_to_array($el->attributes) as $attr) {
                $keep = $el->nodeName === 'a' && $attr->name === 'href'
                    && preg_match('#^(https?://|mailto:|tel:|/)#i', trim($attr->value));
                if (!$keep) $el->removeAttributeNode($attr);
            }
            if ($el->nodeName === 'a') {
                $el->setAttribute('rel', 'noopener noreferrer nofollow');
                $el->setAttribute('target', '_blank');
            }
        }
        $wrap = $doc->getElementById('wrap');
        $out = '';
        foreach ($wrap->childNodes as $child) $out .= $doc->saveHTML($child);
        return trim($out);
    }
}









