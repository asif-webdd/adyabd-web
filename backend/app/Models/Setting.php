<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'group', 'value', 'type', 'label', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $s = static::where('key', $key)->first();

        if (!$s) {
            return $default;
        }

        return match ($s->type) {
            'boolean' => (bool) $s->value,
            'number'  => (float) $s->value,
            'json'    => json_decode($s->value, true),
            default   => $s->value,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
            ]
        );

        cache()->forget("setting_{$key}");
    }
}
