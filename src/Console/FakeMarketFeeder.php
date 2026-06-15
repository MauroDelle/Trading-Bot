<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Data\MarketDataStreamer;
use App\Data\PriceTick;
use App\Factory\KafkaProducerFactory;

echo "=============================================\n";
echo "📈 Iniciando Fake Market Feeder (Multi-Token)\n";
echo "=============================================\n\n";

$factory = new KafkaProducerFactory();
$producer = $factory->create('kafka:29092');
$streamer = new MarketDataStreamer($producer);

$configs = require __DIR__ . '/../../config/strategies.php';
$prices = [];

// Inicializar precios base en el medio del grid de cada token
foreach ($configs as $symbol => $c) {
    $prices[$symbol] = ($c['upperPrice'] + $c['lowerPrice']) / 2;
}

while (true) {
    foreach ($configs as $symbol => $c) {
        // Volatilidad del 0.5% del valor de la moneda
        $volatility = $prices[$symbol] * 0.005; 
        $change = (mt_rand(-100, 100) / 100.0) * $volatility;
        
        $prices[$symbol] += $change;

        // Limites para que no se salga infinitamente del grid
        if ($prices[$symbol] > $c['upperPrice'] * 1.1) $prices[$symbol] = $c['upperPrice'] * 1.1;
        if ($prices[$symbol] < $c['lowerPrice'] * 0.9) $prices[$symbol] = $c['lowerPrice'] * 0.9;

        $tick = new PriceTick($symbol, $prices[$symbol], time());
        $topic = 'market.ticker.' . strtolower(str_replace('/', '_', $symbol));
        $streamer->streamTick($tick, $topic);

        echo "[Fake Tick] $symbol -> $" . number_format($prices[$symbol], 2) . "   \n";
    }
    $producer->poll(0);
    echo "----------------------------------------\n";
    sleep(1);
}