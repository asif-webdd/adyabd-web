<?php

use App\Models\{AutoBlockRule, NotificationTemplate};
use Database\Seeders\{AutoBlockRulesSeeder, NotificationTemplatesSeeder};
use Illuminate\Database\Migrations\Migration;

// Older installs never got SMS templates / auto-block rules (DatabaseSeeder skipped them),
// so those admin pages were empty. Fill them ONLY when empty – nothing the admin edited is overwritten.
return new class extends Migration {
    public function up(): void
    {
        ob_start();
        try {
            if (NotificationTemplate::count() === 0) {
                (new NotificationTemplatesSeeder())->run();
            }
            if (AutoBlockRule::count() === 0) {
                (new AutoBlockRulesSeeder())->run();
            }
        } finally {
            ob_end_clean();
        }
    }

    public function down(): void
    {
    }
};
