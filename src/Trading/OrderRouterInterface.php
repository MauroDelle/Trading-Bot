<?php

declare(strict_types=1);

namespace App\Trading;

interface OrderRouterInterface
{
    public function routeOrder(string $symbol, string $side, float $amount, float $price): void;

    public function placeMarketBuyOrder(string $symbol, float $amount): array;
    
    public function placeMarketSellOrder(string $symbol, float $amount): array;
    
    public function getBalance(string $asset): float;
}