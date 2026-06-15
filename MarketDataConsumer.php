<?php

declare(strict_types=1);

namespace App\Data;

use App\Repository\PriceRepositoryInterface;
use JsonException;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RuntimeException;

class MarketDataConsumer
{
    public function __construct(
        private KafkaConsumer $consumer,
        private PriceRepositoryInterface $priceRepository
    ) {
    }

    /**
     * Consume un solo evento. Ideal para ser llamado dentro de un Event Loop.
     */
    public function consume(int $timeoutMs = 100): ?string
    {
        $message = $this->consumer->consume($timeoutMs);
        
        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                return $this->processMessage($message);
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                // Comportamiento normal, no hay mensajes nuevos o fin de la partición
                break;
            default:
                throw new RuntimeException($message->errstr(), $message->err);
        }
        
        return null;
    }

    /**
     * Ejecuta el consumer en un loop continuo. (Ejecución recomendada para FrankenPHP Worker)
     */
    public function startLoop(): void
    {
        while (true) {
            $this->consume(100);
            
            // CRÍTICO: Recolección de basura explícita para prevenir fugas de memoria en workers residentes.
            gc_collect_cycles();
        }
    }

    private function processMessage(Message $message): ?string
    {
        if ($message->payload === null) {
            return null;
        }
        
        $tick = PriceTick::fromJson($message->payload);
        $this->priceRepository->saveLastPrice($tick->symbol, $tick->price);
        return $tick->symbol;
    }
}