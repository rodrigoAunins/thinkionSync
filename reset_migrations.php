<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Cleaning migrations table..." . PHP_EOL;
try {
    $deleted = DB::table('migrations')
        ->where('migration', 'like', '%thinkion_sync_tables%')
        ->delete();
    echo "Deleted $deleted entries for thinkion_sync_tables." . PHP_EOL;
} catch (\Exception $e) {
    echo "FAIL: " . $e->getMessage() . PHP_EOL;
}
