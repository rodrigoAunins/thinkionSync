<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function dumpTable($conn, $table) {
    echo "COLUMNS FOR $table:" . PHP_EOL;
    try {
        $cols = DB::connection($conn)->select("SHOW COLUMNS FROM $table");
        foreach ($cols as $col) {
            echo " - {$col->Field} ({$col->Type})" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "FAIL: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
}

dumpTable('mysql', 'articulo_ventas');
dumpTable('mysql', 'vinson_product_mix');
