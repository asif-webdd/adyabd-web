<?php

use App\Http\Middleware\CheckBlocked;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
)->withMiddleware(function (Middleware $middleware) {
    // Behind the host's HTTPS proxy: trust X-Forwarded-* so Laravel sees https (needed for signed URLs, e.g. Livewire/Filament file uploads)
    $middleware->trustProxies(at: '*');
    
    $middleware->alias([
        'check.blocked' => CheckBlocked::class,
        'seller'        => \App\Http\Middleware\EnsureSeller::class,
        'role'          => RoleMiddleware::class,
        'permission'    => PermissionMiddleware::class,
    ]);

    $middleware->api(prepend: [
        //\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
})->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthenticationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in to continue.',
                'code'    => 'UNAUTHENTICATED',
            ], 401);
        }
    });

    $exceptions->render(function (ValidationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        }
    });

    $exceptions->render(function (ModelNotFoundException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        }
    });

    $exceptions->render(function (HttpException $e, $request) {
        if ($request->is('api/*') && $e->getStatusCode() === 403) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to perform this action.',
            ], 403);
        }
    });

})->create();
