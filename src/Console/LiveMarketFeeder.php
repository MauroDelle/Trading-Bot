<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Data\MarketDataStreamer;
use App\Data\PriceTick;
use App\Factory\KafkaProducerFactory;
use ccxt\binance;

echo "=============================================\n";
echo "🌐 Iniciando Live Market Feeder (Binance Testnet)\n";
echo "=============================================\n\n";

$factory = new KafkaProducerFactory();
$producer = $factory->create('kafka:29092');
$streamer = new MarketDataStreamer($producer);

$exchange = new binance([
    'enableRateLimit' => true,
]);
// Activar modo Testnet (Paper Trading de Binance)
$exchange->set_sandbox_mode(true);

$symbol = 'BTC/USDT';

echo "Conectando a Binance Testnet para obtener precios de $symbol...\n\n";

while (true) {
    try {
        $ticker = $exchange->fetch_ticker($symbol);
        $price = (float)$ticker['last'];

        $tick = new PriceTick($symbol, $price, time());
        $streamer->streamTick($tick, 'market.ticker.btc_usdt');

        echo "[Live Tick] $symbol -> $" . number_format($price, 2) . "\n";
        
        // Esperar 2 segundos para no saturar la API (Rate Limits)
        sleep(2);
    } catch (\Exception $e) {
        echo "[Error CCXT] " . $e->getMessage() . " - Reintentando en 3s...\n";
        sleep(3);
    }
}