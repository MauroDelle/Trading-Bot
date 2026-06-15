<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Data\MarketDataConsumer;
use App\Factory\KafkaConsumerFactory;
use App\Repository\RedisPriceRepository;
use App\Repository\PostgresTradeRepository;
use App\Trading\GridConfiguration;
use App\Notification\TelegramNotifier;
use App\Trading\RedisGridStateManager;
use App\Trading\RedisPaperTradingSimulator;
use App\Trading\SpotGridStrategy;
use Predis\Client;

echo "=============================================\n";
echo "🤖 Iniciando Bot Runner (Spot Grid Worker)\n";
echo "=============================================\n\n";

$redis = new Client(['host' => 'redis', 'port' => 6379]);
$configs = require __DIR__ . '/../../config/strategies.php';

$priceRepo = new RedisPriceRepository($redis);
$stateManager = new RedisGridStateManager($redis);
$orderRouter = new RedisPaperTradingSimulator($redis);

// Opcional: Reemplaza con tus credenciales de Telegram para activar los avisos al celular
$telegramBotToken = ''; // Ej: '123456789:ABCdefGHIjklMNOpqrsTUVwxyz'
$telegramChatId = '';   // Ej: '987654321'
$notifier = new TelegramNotifier($telegramBotToken, $telegramChatId);

$pdo = new \PDO('pgsql:host=postgres;port=5432;dbname=tradingbot', 'botuser', 'botpassword', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
$tradeRepo = new PostgresTradeRepository($pdo);

$strategies = [];
$topics = [];
$initialUsdt = 0.0;

// Orquestador de N estrategias en paralelo
foreach ($configs as $symbol => $c) {
    $initialUsdt += $c['totalInvestment'];
    list($base, $quote) = explode('/', $symbol);
    
    if (!$redis->exists("balance:{$base}")) {
        $redis->set("balance:{$base}", 0.0);
    }
    $gridConfig = new GridConfiguration($c['upperPrice'], $c['lowerPrice'], $c['totalGrids'], $c['totalInvestment']);
    $strategies[$symbol] = new SpotGridStrategy($priceRepo, $orderRouter, $gridConfig, $stateManager, $tradeRepo, $notifier);
    $topics[] = 'market.ticker.' . strtolower($base . '_' . $quote);
}

if (!$redis->exists('balance:USDT')) $redis->set('balance:USDT', $initialUsdt);

$kafkaFactory = new KafkaConsumerFactory();
$kafkaConsumer = $kafkaFactory->create('kafka:29092', 'grid_bot_group', $topics);
$consumer = new MarketDataConsumer($kafkaConsumer, $priceRepo);

echo "Escuchando el mercado en tiempo real...\n\n";

$ticksProcessed = 0;

while (true) {
    // Bloquea hasta 1000ms esperando un tick. Al llegar, lo guarda en el repositorio.
    try {
        $symbol = $consumer->consume(1000);
    } catch (\Exception $e) {
        // Si el tópico no existe aún o hay un problema de conexión, esperamos y reintentamos
        echo "Aviso Kafka: " . $e->getMessage() . " - Reintentando en 2 segundos...\n";
        sleep(2);
        continue;
    }

    if ($symbol && isset($strategies[$symbol])) {
        $currentPrice = $priceRepo->getLastPrice($symbol);
        if ($currentPrice !== null) {
            echo "[BotRunner] Analizando mercado... {$symbol} a $" . number_format($currentPrice, 2) . "     \r";
        }
        $strategies[$symbol]->execute($symbol);
    }
    
    if (++$ticksProcessed % 1000 === 0) {
        gc_collect_cycles(); // Ejecutar GC cada 1000 ticks para no penalizar latencia
    }
}