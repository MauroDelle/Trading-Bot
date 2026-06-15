<?php

declare(strict_types=1);

namespace App\Trading;

readonly class GridConfiguration
{
    public function __construct(
        public float $upperPrice,
        public float $lowerPrice,
        public int $totalGrids,
        public float $totalInvestment
    ) {
    }

    public function getLevels(): array
    {
        $step = ($this->upperPrice - $this->lowerPrice) / $this->totalGrids;
        $levels = [];
        for ($i = 0; $i <= $this->totalGrids; $i++) {
            $levels[] = $this->lowerPrice + ($step * $i);
        }
        return $levels;
    }

    public function getInvestmentPerGrid(): float
    {
        return $this->totalInvestment / $this->totalGrids;
    }
}