<?php

declare(strict_types=1);

namespace App\Factory;

use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class KafkaConsumerFactory
{
    public function create(string $brokers, string $groupId, array $topics): KafkaConsumer
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $brokers);
        $conf->set('group.id', $groupId);
        
        // Garantizamos que, si es la primera vez que se conecta el worker, lea los eventos desde el principio
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.partition.eof', 'true'); // Recomendado para evitar bloqueos en rdkafka

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe($topics);

        return $consumer;
    }
}