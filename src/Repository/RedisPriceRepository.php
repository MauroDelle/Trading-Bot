<?php

declare(strict_types=1);

namespace App\Repository;

use Predis\Client;

class RedisPriceRepository implements PriceRepositoryInterface
{
    public function __construct(
        private Client $redis
    ) {
    }

    public function saveLastPrice(string $symbol, float $price): void
    {
        $this->redis->set("price:{$symbol}", $price);
    }

    public function getLastPrice(string $symbol): ?float
    {
        $value = $this->redis->get("price:{$symbol}");
        return $value !== null ? (float)$value : null;
    }
}