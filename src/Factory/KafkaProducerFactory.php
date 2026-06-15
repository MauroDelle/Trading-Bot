<?php

declare(strict_types=1);

namespace App\Factory;

use RdKafka\Conf;
use RdKafka\Producer;

class KafkaProducerFactory
{
    public function create(string $brokers): Producer
    {
        $conf = new Conf();
        
        // Configuramos los brokers a los que el productor se conectará
        $conf->set('metadata.broker.list', $brokers);
        
        // Configuraciones recomendadas para fiabilidad y baja latencia
        $conf->set('socket.timeout.ms', '50');
        
        return new Producer($conf);
    }
}