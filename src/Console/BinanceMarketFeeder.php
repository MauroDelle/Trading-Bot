<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

// CCXT carga miles de mercados en memoria la primera vez que se conecta. 
// Aumentamos el límite de memoria para evitar que el Worker colapse.
ini_set('memory_limit', '512M');

use App\Data\MarketDataStreamer;
use App\Data\PriceTick;
use App\Factory\KafkaProducerFactory;
use WebSocket\Client;
use WebSocket\ConnectionException;

echo "=============================================\n";
echo "🌐 Iniciando Binance Market Feeder (WebSockets)\n";
echo "=============================================\n\n";

$factory = new KafkaProducerFactory();
$producer = $factory->create('kafka:29092');
$streamer = new MarketDataStreamer($producer);

$wsUrl = 'wss://stream.binance.com:9443/stream?streams=btcusdt@ticker/ethusdt@ticker/solusdt@ticker';

$keepRunning = true;
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, function() use (&$keepRunning) { $keepRunning = false; echo "\nDeteniendo Feeder...\n"; });
    pcntl_signal(SIGTERM, function() use (&$keepRunning) { $keepRunning = false; echo "\nDeteniendo Feeder...\n"; });
}

$backoff = 1;

echo "Preparando conexión de ultra-baja latencia a Binance...\n\n";

while ($keepRunning) {
    try {
        echo "Conectando a WebSocket: $wsUrl\n";
        $client = new Client($wsUrl, ['timeout' => 60]);
        echo "✅ Conectado a Binance WebSockets. Recibiendo stream en tiempo real...\n";
        $backoff = 1; // Resetear backoff en conexión exitosa

        while ($keepRunning) {
            $message = $client->receive();
            $data = json_decode($message, true);

            // Payload combinado usa formato {"stream": "...", "data": {"s": "BTCUSDT", "c": "65000"}}
            if (isset($data['data']['c']) && isset($data['data']['s'])) {
                $rawSymbol = $data['data']['s'];
                $price = (float)$data['data']['c'];
                
                // Mapeo a nuestra nomenclatura interna
                $map = ['BTCUSDT' => 'BTC/USDT', 'ETHUSDT' => 'ETH/USDT', 'SOLUSDT' => 'SOL/USDT'];
                $symbol = $map[$rawSymbol] ?? null;
                
                if ($symbol) {
                    $tick = new PriceTick($symbol, $price, time());
                    $topic = 'market.ticker.' . strtolower(str_replace('/', '_', $symbol));
                    $streamer->streamTick($tick, $topic);
                    $producer->poll(0);
                }
            }
        }
    } catch (ConnectionException $e) {
        echo "\n[Error WS] Desconexión: " . $e->getMessage() . "\n";
    } catch (\Exception $e) {
        echo "\n[Error General] " . $e->getMessage() . "\n";
    }

    if ($keepRunning) {
        echo "Reintentando conexión en {$backoff} segundos...\n";
        sleep($backoff);
        $backoff = min($backoff * 2, 60); // Límite de espera de 60 segundos
    }
}

echo "Feeder de WebSockets cerrado de forma segura.\n";