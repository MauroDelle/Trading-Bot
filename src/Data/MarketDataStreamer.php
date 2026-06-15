<?php

declare(strict_types=1);

namespace App\Data;

use RdKafka\Producer;

class MarketDataStreamer
{
    public function __construct(
        private Producer $producer
    ) {
    }

    public function streamTick(PriceTick $tick, string $topicName): void
    {
        $topic = $this->producer->newTopic($topicName);
        
        // RD_KAFKA_PARTITION_UA auto-asigna la partición. Se envía el payload en JSON.
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $tick->toJson());
        $this->producer->poll(0); // Ejecuta el envío de forma no bloqueante
    }
}