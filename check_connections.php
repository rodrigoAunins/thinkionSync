<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Try MySQL
try {
    config([
        'database.connections.mysql.host' => '127.0.0.1',
        'database.connections.mysql.port' => '3306',
        'database.connections.mysql.database' => 'vinson_etl',
        'database.connections.mysql.username' => 'root',
        'database.connections.mysql.password' => 'root',
    ]);
    DB::connection('mysql')->getPdo();
    echo "MYSQL: CONNECTED TO vinson_etl" . PHP_EOL;
    $tables = DB::connection('mysql')->select("SHOW TABLES");
    foreach ($tables as $table) {
        $key = "Tables_in_vinson_etl";
        echo " - Table: " . $table->$key . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "MYSQL: FAILED - " . $e->getMessage() . PHP_EOL;
}

// Try PostgreSQL
try {
    DB::connection('pgsql')->getPdo();
    echo "PGSQL: CONNECTED TO thinkion_etl" . PHP_EOL;
} catch (\Exception $e) {
    echo "PGSQL: FAILED - " . $e->getMessage() . PHP_EOL;
}
