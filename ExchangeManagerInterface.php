<?php

declare(strict_types=1);

namespace App\Exchange;

interface ExchangeManagerInterface
{
    /**
     * Obtiene el ticker actual de un par de mercado.
     * @param string $symbol Ej. 'BTC/USDT'
     * @return array<string, mixed> Datos del ticker (incluyendo el precio 'last')
     */
    public function fetchTicker(string $symbol): array;
}