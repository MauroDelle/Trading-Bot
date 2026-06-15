<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Predis\Client;

echo "=============================================\n";
echo "🧹 Limpiando el Estado del Bot en Redis\n";
echo "=============================================\n\n";

$redis = new Client(['host' => 'redis', 'port' => 6379]);
$configs = require __DIR__ . '/../../config/strategies.php';

// Limpiamos los estados de las líneas de Grid anteriores
$keys = $redis->keys('grid:state:*');
foreach ($keys as $key) {
    $redis->del($key);
}

$keysToDel = ['balance:USDT'];
foreach ($configs as $symbol => $c) {
    list($base, $quote) = explode('/', $symbol);
    $keysToDel[] = "balance:{$base}";
    $keysToDel[] = "price:{$symbol}";
}
$redis->del($keysToDel);

echo "✅ Estado del Grid limpiado.\n";
echo "✅ Balances y precios reseteados.\n\n";
echo "El sistema está listo para arrancar en limpio con el precio real.\n";