<?php

declare(strict_types=1);

namespace App\Trading;

use Predis\Client;

class RedisGridStateManager implements GridStateManagerInterface
{
    public function __construct(
        private Client $redis
    ) {
    }

    public function getState(string $symbol, int $levelIndex): string
    {
        $state = $this->redis->get("grid:state:{$symbol}:{$levelIndex}");
        return $state !== null ? (string)$state : 'PENDING';
    }

    public function setState(string $symbol, int $levelIndex, string $state): void
    {
        $this->redis->set("grid:state:{$symbol}:{$levelIndex}", $state);
    }
}