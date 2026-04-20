<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$client = app(App\Services\Thinkion\ApiClient::class);

echo "--- Report #2 sample ---" . PHP_EOL;
$res2 = $client->fetchReport(2, '2024-01-01', '2024-01-01', [1, 2]);
print_r(array_slice($res2['data'] ?? [], 0, 1));

echo "--- Report #3 sample ---" . PHP_EOL;
$res3 = $client->fetchReport(3, '2024-01-01', '2024-01-01', [1, 2]);
print_r(array_slice($res3['data'] ?? [], 0, 1));
