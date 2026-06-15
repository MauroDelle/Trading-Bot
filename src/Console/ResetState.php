<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Predis\Client;

echo "=============================================\n";
echo "🧹 Limpiando el Estado del Bot en Redis\n";
echo "=============================================\n\n";

$redis = new Client(['host' => 'redis', 'port' => 6379]);

// Limpiamos los estados de las líneas de Grid anteriores
$keys = $redis->keys('grid:state:*');
foreach ($keys as $key) {
    $redis->del($key);
}

$redis->del(['balance:USDT', 'balance:BTC', 'price:BTC/USDT']);

echo "✅ Estado del Grid limpiado.\n";
echo "✅ Balances ficticios y precio reseteados.\n\n";
echo "El sistema está listo para arrancar en limpio con el precio real.\n";