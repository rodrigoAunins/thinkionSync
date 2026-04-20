<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$client = app(App\Services\Thinkion\ApiClient::class);
$reportIds = range(235, 250);
$establishments = [1, 2];
$dateInit = '2024-01-01';
$dateEnd = '2024-01-05';

foreach ($reportIds as $id) {
    echo "Checking report #$id..." . PHP_EOL;
    try {
        $result = $client->fetchReport($id, $dateInit, $dateEnd, $establishments);
        if (!empty($result['data'])) {
            echo "SUCCESS! Report #$id has data. Columns: " . implode(', ', array_keys($result['data'][0])) . PHP_EOL;
        } else {
            echo "No data for #$id" . PHP_EOL;
        }
    } catch (\Exception $e) {
        // Skip errors
    }
}
