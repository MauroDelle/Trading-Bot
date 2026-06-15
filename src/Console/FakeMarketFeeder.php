<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Data\MarketDataStreamer;
use App\Data\PriceTick;
use App\Factory\KafkaProducerFactory;

echo "=============================================\n";
echo "📈 Iniciando Fake Market Feeder (Simulador)\n";
echo "=============================================\n\n";

$factory = new KafkaProducerFactory();
// Se conecta al broker interno de Kafka en el entorno Docker
$producer = $factory->create('kafka:29092');
$streamer = new MarketDataStreamer($producer);

$price = 65500.0; // Precio inicial cerca del mercado actual

while (true) {
    // Caminata aleatoria de -500 a +500 USDT para simular volatilidad extrema
    $change = mt_rand(-50000, 50000) / 100.0;
    $price += $change;

    // Mantenemos el precio oscilando dentro del grid de 60k a 70k
    if ($price > 71000.0) $price = 71000.0;
    if ($price < 59000.0) $price = 59000.0;

    $tick = new PriceTick('BTC/USDT', $price, time());
    $streamer->streamTick($tick, 'market.ticker.btc_usdt');

    echo "[Market Tick] BTC/USDT -> " . number_format($price, 2) . " USDT\n";
    
    // Simula 1 tick por segundo
    sleep(1);
}