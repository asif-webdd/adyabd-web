<?php
// Opens Filament admin pages as the first admin and lists the failures.
// usage: php tools/crawl_admin.php list | php crawl_admin.php <index>
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->instance('request', Illuminate\Http\Request::create('http://127.0.0.1:8000/admin'));
$kernel->bootstrap();

$panel = Filament\Facades\Filament::getPanel('admin');
$urls = [];
foreach ($panel->getResources() as $r) {
    foreach (['index', 'create'] as $p) {
        if (array_key_exists($p, $r::getPages())) {
            $urls["$r@$p"] = $r::getUrl($p, panel: 'admin');
        }
    }
}
foreach ($panel->getResources() as $r) {
    $model = $r::getModel();
    $rec = $model::query()->first();
    if (!$rec) continue;
    foreach (['view', 'edit'] as $p) {
        if (array_key_exists($p, $r::getPages())) {
            $urls["$r@$p"] = $r::getUrl($p, ['record' => $rec], panel: 'admin');
        }
    }
}
foreach ($panel->getPages() as $pg) {
    $urls[$pg] = $pg::getUrl(panel: 'admin');
}

$only = $argv[1] ?? null;
if ($only === 'list') {
    foreach (array_keys($urls) as $i => $l) echo "$i $l\n";
    exit;
}
if ($only !== null) {
    $urls = array_slice($urls, (int) $only, 1, true);
}

$bad = 0;
foreach ($urls as $label => $url) {
    $short = preg_replace('~^App\\\\Filament\\\\~', '', $label);
    try {
        $admin = App\Models\AdminUser::first();
        $app['auth']->shouldUse('admin');
        $app['auth']->guard('admin')->setUser($admin);
        $res = $kernel->handle(Illuminate\Http\Request::create($url, 'GET'));
        $code = $res->getStatusCode();
        if ($code >= 400 || ($code >= 300 && !str_contains((string) $res->headers->get('Location', ''), '/admin'))) {
            $bad++;
            echo "FAIL $code  $short\n";
        } else {
            echo "ok   $code  $short\n";
        }
    } catch (Throwable $e) {
        $bad++;
        echo "FAIL EXC $short → " . get_class($e) . ': ' . substr(str_replace("\n", ' ', $e->getMessage()), 0, 220) . "\n";
    }
}
exit($bad ? 1 : 0);
