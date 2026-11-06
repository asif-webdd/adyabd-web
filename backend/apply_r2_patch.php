<?php
/**
 * Cloudflare R2 patch helper  –  run from the backend folder:   php apply_r2_patch.php
 *
 * 1) replaces  asset('storage/' . <expr>)  /  asset("storage/$x")  with  storage_url(<expr>)  in app/ and resources/views/
 * 2) removes  ->visibility('public')  from app/Filament/ (the R2 setup sets visibility globally)
 * Every changed file is backed up next to itself as  <file>.r2bak  first. Safe to run twice.
 * It does NOT touch the file that defines storage_url() (app/Helpers/helpers.php).
 */
$root = __DIR__;
$changed = 0;

/** index of the ")" that closes the "(" at $open */
function closing(string $s, int $open): int {
    $depth = 0; $q = null;
    for ($i = $open, $n = strlen($s); $i < $n; $i++) {
        $c = $s[$i];
        if ($q) { if ($c === '\\') { $i++; } elseif ($c === $q) { $q = null; } continue; }
        if ($c === "'" || $c === '"') { $q = $c; continue; }
        if ($c === '(') $depth++;
        if ($c === ')') { $depth--; if ($depth === 0) return $i; }
    }
    return -1;
}

function convert(string $s): string {
    // asset('storage/' . EXPR)
    $needle = "asset('storage/'";
    $pos = 0;
    while (($p = strpos($s, $needle, $pos)) !== false) {
        $open = $p + 5;                       // the "(" after asset
        $close = closing($s, $open);
        if ($close < 0) break;
        $inner = trim(substr($s, $open + 1, $close - $open - 1));      // 'storage/' . EXPR
        if (preg_match("/^'storage\\/'\\s*\\.\\s*(.+)$/s", $inner, $m)) {
            $s = substr($s, 0, $p) . 'storage_url(' . trim($m[1]) . ')' . substr($s, $close + 1);
            $pos = $p + 12;
        } else {
            $pos = $p + 5;
        }
    }
    // asset("storage/$this->image_path")   asset("storage/{$item->image}")   asset("storage/$path")
    $s = preg_replace_callback('/asset\("storage\/(\{?)(\$[A-Za-z_][A-Za-z0-9_]*(?:->[A-Za-z_][A-Za-z0-9_]*)*)\}?"\)/', fn($m) => 'storage_url(' . $m[2] . ')', $s);
    return $s;
}

$dirs = ['app', 'resources/views'];
foreach ($dirs as $d) {
    if (!is_dir("$root/$d")) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!preg_match('/\.php$/', $f->getFilename())) continue;
        $path = $f->getPathname();
        $src = file_get_contents($path);
        if (str_contains($src, 'function storage_url')) continue;   // helper definition: never touch
        $new = convert($src);
        if (str_contains($path, DIRECTORY_SEPARATOR . 'Filament' . DIRECTORY_SEPARATOR)) {
            $new = preg_replace("/\\s*->visibility\\('public'\\)/", '', $new);
        }
        if ($new !== $src) {
            copy($path, $path . '.r2bak');
            file_put_contents($path, $new);
            echo "changed: " . str_replace($root . DIRECTORY_SEPARATOR, '', $path) . PHP_EOL;
            $changed++;
        }
    }
}
echo $changed ? "\nDone: $changed file(s) changed (backups: *.r2bak).\n" : "\nNothing to change (already patched).\n";