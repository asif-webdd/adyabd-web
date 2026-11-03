<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Adds admin-editable invoice numbering settings (Settings → General →
 * Invoice Numbering): prefix, pattern, and zero-padded sequence length.
 * Order::generateOrderNumber() reads these; defaults match the previous
 * hardcoded "BD-2026-00001" format so existing behaviour is unchanged
 * until an admin updates them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'invoice_prefix'],
            ['value' => 'BD', 'group' => 'general', 'type' => 'text', 'label' => 'Invoice Prefix', 'is_public' => false]
        );

        Setting::updateOrCreate(
            ['key' => 'invoice_pattern'],
            ['value' => 'PREFIX-YEAR-SEQ', 'group' => 'general', 'type' => 'text', 'label' => 'Invoice Pattern', 'is_public' => false]
        );

        Setting::updateOrCreate(
            ['key' => 'invoice_sequence_length'],
            ['value' => '5', 'group' => 'general', 'type' => 'number', 'label' => 'Invoice Sequence Digits', 'is_public' => false]
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', ['invoice_prefix', 'invoice_pattern', 'invoice_sequence_length'])->delete();
    }
};
