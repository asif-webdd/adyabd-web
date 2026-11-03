<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'name', 'title', 'type', 'subject', 'body', 'variables', 'is_active', 'is_system',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function parse(array $data): string
    {
        $pairs = [];
        foreach ($data as $k => $v) {
            $pairs['{' . $k . '}'] = (string) $v;
        }

        return strtr($this->body, $pairs);
    }

    public static function getTemplate(string $name, string $type = 'sms'): ?self
    {
        return static::where('name', $name)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }
}
