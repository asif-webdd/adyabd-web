<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
// Seller commission becomes optional: NULL / blank = "use the global rate from Settings → Commission (%)".
return new class extends Migration {
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->default(null)->change();
        });
    }
 
    public function down(): void
    {
        // not reversed: NULL rows would have no value to fall back to
    }
};