<?php

namespace App\Support;

/**
 * Rebuilds a valid PEM key from a value that a hosting panel / .env file may have mangled:
 * real line breaks lost (replaced by spaces), literal "\n" sequences, surrounding quotes, or a base64-encoded PEM.
 * Returns null for an empty value so Passport falls back to the storage/oauth-*.key files.
 */
class PemKey
{
    public static function normalize(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        $v = trim($v, "\"' \t\r\n");

        // base64 of a whole PEM file
        if (!str_contains($v, '-----BEGIN')) {
            $decoded = base64_decode($v, true);
            if ($decoded !== false && str_contains($decoded, '-----BEGIN')) {
                $v = $decoded;
            } else {
                return $v; // not something we understand: hand it over unchanged
            }
        }

        $v = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $v);

        if (!preg_match('/-----BEGIN ([A-Z ]+)-----(.*?)-----END \1-----/s', $v, $m)) {
            return $v;
        }
        $label = $m[1];
        $body  = preg_replace('/\s+/', '', $m[2]);

        return "-----BEGIN {$label}-----\n" . chunk_split($body, 64, "\n") . "-----END {$label}-----\n";
    }
}