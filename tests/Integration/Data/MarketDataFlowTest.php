<?php

declare(strict_types=1);

namespace Tests\Integration\Data;

use App\Data\MarketDataConsumer;
use App\Data\PriceTick;
use App\Data\MarketDataStreamer;
use App\Repository\RedisPriceRepository;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer;
use RdKafka\ProducerTopic;

class MarketDataFlowTest extends TestCase
{
    private Client $redis;
    private RedisPriceRepository $repository;

    protected function setUp(): void
    {
        // Nos conectamos al contenedor real de Redis
        $this->redis = new Client([
            'scheme'   => 'tcp',
            'host'     => 'redis',
            'port'     => 6379,
            'database' => 15 // Usamos un slot de DB aislado para evitar ensuciar los datos de dev
        ]);
        $this->redis->flushdb();
        
        $this->repository = new RedisPriceRepository($this->redis);
    }

    public function testConsumerProcessesKafkaMessageAndUpdatesRedis(): void
    {
        // 1. Mockeamos Kafka para simular un mensaje entrante
        $kafkaConsumerMock = $this->createMock(KafkaConsumer::class);
        
        $tick = new PriceTick('BTC/USDT', 51000.50, time());
        $message = new Message();
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->payload = $tick->toJson();
        
        $kafkaConsumerMock->expects($this->once())
            ->method('consume')
            ->willReturn($message);
            
        // 2. Ejecutamos el flujo del consumidor
        $consumer = new MarketDataConsumer($kafkaConsumerMock, $this->repository);
        $consumer->consume(100);
        
        // 3. Afirmamos sobre la integración real (Lectura O(1) de Redis)
        $price = $this->repository->getLastPrice('BTC/USDT');
        
        $this->assertSame(51000.50, $price);
    }

    public function testProducerStreamsMessageToKafka(): void
    {
        $producerMock = $this->createMock(Producer::class);
        $topicMock = $this->createMock(ProducerTopic::class);
        
        $producerMock->expects($this->once())
            ->method('newTopic')
            ->with('market.ticker.btc_usdt')
            ->willReturn($topicMock);
            
        $topicMock->expects($this->once())
            ->method('produce');
            
        $streamer = new MarketDataStreamer($producerMock);
        $streamer->streamTick(new PriceTick('BTC/USDT', 52000.0, time()), 'market.ticker.btc_usdt');
    }
}