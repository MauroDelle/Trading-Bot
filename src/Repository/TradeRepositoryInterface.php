<?php

declare(strict_types=1);

namespace App\Repository;

interface TradeRepositoryInterface
{
    public function logTrade(string $symbol, string $side, float $amount, float $price, int $gridLevel, ?float $realizedProfit = null): void;
}