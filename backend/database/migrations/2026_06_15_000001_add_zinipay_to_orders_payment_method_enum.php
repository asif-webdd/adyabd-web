<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds 'zinipay' as a valid orders.payment_method value.
 *
 * Existing values are kept so historic orders placed via bKash, Nagad,
 * Rocket, Card, or EMI continue to display correctly — new orders are
 * restricted to cod / zinipay / wallet at the application validation
 * layer (see OrderController::store).
 */
return new class extends Migration
{
    public function up(): void
    {
        // MODIFY ... ENUM is MySQL/MariaDB syntax only (this project targets MySQL).
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cod', 'bkash', 'nagad', 'rocket', 'card', 'wallet', 'emi', 'zinipay') DEFAULT 'cod'");
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cod', 'bkash', 'nagad', 'rocket', 'card', 'wallet', 'emi') DEFAULT 'cod'");
    }
};
