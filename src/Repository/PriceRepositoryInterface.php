<?php

declare(strict_types=1);

namespace App\Repository;

interface PriceRepositoryInterface
{
    public function saveLastPrice(string $symbol, float $price): void;
    
    public function getLastPrice(string $symbol): ?float;
}