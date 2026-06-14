<?php

declare(strict_types=1);

namespace App\Trading;

use App\Exchange\ExchangeManagerInterface;
use RuntimeException;

class PaperTradingSimulator implements OrderRouterInterface
{
    /** @var array<string, float> */
    private array $balances = [];

    public function __construct(
        private ExchangeManagerInterface $exchangeManager,
        array $initialBalances = []
    ) {
        $this->balances = $initialBalances;
    }

    public function placeMarketBuyOrder(string $symbol, float $amount): array
    {
        [$base, $quote] = explode('/', $symbol);
        
        $ticker = $this->exchangeManager->fetchTicker($symbol);
        $price = $ticker['last'] ?? throw new RuntimeException("Precio 'last' no disponible en el ticker de {$symbol}");
        
        $cost = $amount * $price;
        $currentQuoteBalance = $this->getBalance($quote);
        
        if ($currentQuoteBalance < $cost) {
            throw new RuntimeException("Balance insuficiente de {$quote} para ejecutar la compra simulada.");
        }

        // Actualizamos los balances ficticios
        $this->balances[$quote] -= $cost;
        $this->balances[$base] = $this->getBalance($base) + $amount;

        return $this->buildOrderReceipt($symbol, 'buy', $amount, $price, $cost);
    }

    public function placeMarketSellOrder(string $symbol, float $amount): array
    {
        [$base, $quote] = explode('/', $symbol);
        
        $currentBaseBalance = $this->getBalance($base);
        if ($currentBaseBalance < $amount) {
            throw new RuntimeException("Balance insuficiente de {$base} para ejecutar la venta simulada.");
        }

        $ticker = $this->exchangeManager->fetchTicker($symbol);
        $price = $ticker['last'] ?? throw new RuntimeException("Precio 'last' no disponible en el ticker de {$symbol}");
        
        $revenue = $amount * $price;

        // Actualizamos los balances ficticios
        $this->balances[$base] -= $amount;
        $this->balances[$quote] = $this->getBalance($quote) + $revenue;

        return $this->buildOrderReceipt($symbol, 'sell', $amount, $price, $revenue);
    }

    public function getBalance(string $asset): float
    {
        return $this->balances[$asset] ?? 0.0;
    }

    private function buildOrderReceipt(string $symbol, string $side, float $amount, float $price, float $cost): array
    {
        return [
            'id'     => uniqid('paper_', true),
            'symbol' => $symbol,
            'type'   => 'market',
            'side'   => $side,
            'amount' => $amount,
            'price'  => $price,
            'cost'   => $cost,
            'status' => 'closed',
        ];
    }
}