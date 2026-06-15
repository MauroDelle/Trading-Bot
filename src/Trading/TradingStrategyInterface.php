<?php

declare(strict_types=1);

namespace App\Trading;

interface TradingStrategyInterface
{
    public function execute(string $symbol): void;
}