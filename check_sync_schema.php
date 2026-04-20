<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function checkTable($table) {
    echo "COLUMNS FOR $table:" . PHP_EOL;
    try {
        $cols = DB::select("SHOW COLUMNS FROM $table");
        foreach ($cols as $col) {
            echo " - {$col->Field} ({$col->Type})" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "FAIL: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
}

checkTable('thinkion_sync_runs');
checkTable('thinkion_raw_reports');
