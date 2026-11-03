<?php

use Illuminate\Database\Migrations\Migration;

// Adds every missing district / upazila / thana (64 districts, ~570 areas) to existing installs.
// Existing rows and their ids are left untouched.
return new class extends Migration {
    public function up(): void
    {
        (new \Database\Seeders\BangladeshGeoSeeder())->run();
    }

    public function down(): void
    {
        // data migration — nothing to undo
    }
};
