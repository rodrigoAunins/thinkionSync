<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = DB::connection('mysql')->select("SHOW COLUMNS FROM ventas");
foreach ($cols as $col) {
    if ($col->Null === 'NO' && $col->Default === null && $col->Extra !== 'auto_increment') {
        echo "REQUIRED: {$col->Field} ({$col->Type})" . PHP_EOL;
    }
}
