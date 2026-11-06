<?php

use App\Http\Controllers\OrderInvoiceController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/up', fn() => response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()]));

// API Documentation redirect
Route::get('/api', fn() => redirect('/api/v1/settings/public'));

// Redirect root to admin
Route::get('/', fn() => redirect('/admin'));

// ── Order Invoice (requires Filament auth) ────────────────────────────────
// No 'auth' middleware here — we manually redirect to /admin/login
// so Laravel doesn't look for a non-existent 'login' route name.

Route::prefix("/admin/orders")->name('orders.')->middleware('auth:admin')->group(function (){
    Route::get('/{order}/invoice', [OrderInvoiceController::class, 'show'])->name('invoice');
});
Route::get('/bulk-invoice', [OrderInvoiceController::class, 'bulk'])->middleware('auth:admin')->name('orders.bulk-invoice');

// Customer invoices are only reachable through a time-limited signed URL, which the
// authenticated API (GET /api/v1/orders/{id}/invoice) hands out to the order's owner.
Route::prefix("/customer/orders")->middleware('signed')->group(function (){
    Route::get('/{order:order_number}/invoice', [OrderInvoiceController::class, 'show'])->name('invoice');
});

// ── Uploaded files (/storage/...) ─────────────────────────────────────────
// Normally `php artisan storage:link` makes public/storage point at storage/app/public, and the web
// server serves those files directly. On Windows that symlink often cannot be created (needs admin /
// developer mode), which made every uploaded image/logo show as broken. When the symlink is missing,
// this route serves the same files, so uploads work either way.
Route::get('/storage/{path}', function (string $path) {
    // Uploads live on Cloudflare R2: any old-style /storage/... link (e.g. built by the frontend) goes to the bucket.
    if (uploads_on_r2()) {
        return redirect()->away(storage_url($path), 302, ['Cache-Control' => 'public, max-age=3600']);
    }

    $base = realpath(storage_path('app/public'));
    $file = $base ? realpath($base . DIRECTORY_SEPARATOR . $path) : false;
    abort_unless($file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file), 404);
    return response()->file($file, ['Cache-Control' => 'public, max-age=86400']);
})->where('path', '.*');
