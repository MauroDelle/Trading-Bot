<?php

declare(strict_types=1);

namespace App\Trading;

use Predis\Client;

class RedisPaperTradingSimulator implements OrderRouterInterface
{
    public function __construct(
        private Client $redis
    ) {
    }

    public function routeOrder(string $symbol, string $side, float $amount, float $price): void
    {
        $feeRate = 0.001; // Comisión realista del 0.1% (Binance Spot)
        $grossValue = $amount * $price;
        $fee = $grossValue * $feeRate;

        list($base, $quote) = explode('/', $symbol);

        if ($side === 'BUY') {
            $totalCost = $grossValue + $fee;
            $this->redis->incrbyfloat("balance:{$quote}", -$totalCost);
            $this->redis->incrbyfloat("balance:{$base}", $amount);
            echo "\n[⚡ ORDEN] COMPRA de " . number_format($amount, 4) . " {$base} a $" . number_format($price, 2) . " {$quote} (Comisión: $" . number_format($fee, 4) . ")\n";
        } elseif ($side === 'SELL') {
            $netProceeds = $grossValue - $fee;
            $this->redis->incrbyfloat("balance:{$quote}", $netProceeds);
            $this->redis->incrbyfloat("balance:{$base}", -$amount);
            echo "\n[⚡ ORDEN] VENTA de " . number_format($amount, 4) . " {$base} a $" . number_format($price, 2) . " {$quote} (Comisión: $" . number_format($fee, 4) . ")\n";
        }
    }

    public function placeMarketBuyOrder(string $symbol, float $amount): array
    {
        // En papel de simulador, retornamos un arreglo de respuesta básica
        return ['status' => 'simulated_buy', 'symbol' => $symbol, 'amount' => $amount];
    }

    public function placeMarketSellOrder(string $symbol, float $amount): array
    {
        return ['status' => 'simulated_sell', 'symbol' => $symbol, 'amount' => $amount];
    }

    public function getBalance(string $asset): float
    {
        $balance = $this->redis->get("balance:{$asset}");
        return $balance !== null ? (float)$balance : 0.0;
    }
}