<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function getCols($conn, $table) {
    try {
        return DB::connection($conn)->select("DESCRIBE $table");
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
}

$data = [
    'ventas' => getCols('mysql', 'ventas'),
    'articulo_ventas' => getCols('mysql', 'articulo_ventas'),
    'vinson_product_mix' => getCols('mysql', 'vinson_product_mix'),
];

file_put_contents('schema_dump.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Done" . PHP_EOL;
