<?php

declare(strict_types=1);

namespace App\Trading;

interface GridStateManagerInterface
{
    public function getState(string $symbol, int $levelIndex): string;
    
    public function setState(string $symbol, int $levelIndex, string $state): void;
}