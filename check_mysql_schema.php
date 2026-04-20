<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function checkColumns($conn, $table) {
    echo "--- Table: $table ($conn) ---" . PHP_EOL;
    try {
        $cols = DB::connection($conn)->select("DESCRIBE $table");
        foreach ($cols as $col) {
            echo " - {$col->Field} ({$col->Type})" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
}

checkColumns('mysql', 'ventas');
checkColumns('mysql', 'articulo_ventas');
checkColumns('mysql', 'vinson_product_mix');
