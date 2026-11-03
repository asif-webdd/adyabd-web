<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    protected $fillable = ['slug', 'title', 'content', 'meta_description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    /** HTML that is safe to print on the storefront (admin-authored, still stripped of scripts / handlers). */
    public function safeContent(): ?string
    {
        $html = trim((string) $this->content);
        if ($html === '') {
            return null;
        }
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4><ul><ol><li><a><blockquote><table><thead><tbody><tr><th><td><hr><span><div><img>');
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1="#"', $html);
        return $html;
    }
}
