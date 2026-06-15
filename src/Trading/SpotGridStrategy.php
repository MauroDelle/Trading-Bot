<?php

declare(strict_types=1);

namespace App\Trading;

use App\Repository\PriceRepositoryInterface;
use App\Repository\TradeRepositoryInterface;
use App\Notification\NotificationServiceInterface;

class SpotGridStrategy implements TradingStrategyInterface
{
    public function __construct(
        private PriceRepositoryInterface $priceRepository,
        private OrderRouterInterface $orderRouter,
        private GridConfiguration $config,
        private GridStateManagerInterface $stateManager,
        private TradeRepositoryInterface $tradeRepository,
        private ?NotificationServiceInterface $notifier = null
    ) {
    }

    public function execute(string $symbol): void
    {
        $currentPrice = $this->priceRepository->getLastPrice($symbol);
        if ($currentPrice === null) {
            return; // Esperamos al siguiente tick si no hay precio conocido
        }

        $levels = $this->config->getLevels();
        $step = ($this->config->upperPrice - $this->config->lowerPrice) / $this->config->totalGrids;
        $tradeAmount = $this->config->getInvestmentPerGrid() / $currentPrice;

        foreach ($levels as $index => $levelPrice) {
            $state = $this->stateManager->getState($symbol, $index);

            // CRUCE HACIA ABAJO (Compra)
            if ($state === 'PENDING' && $currentPrice <= $levelPrice) {
                $this->orderRouter->routeOrder($symbol, 'BUY', $tradeAmount, $currentPrice);
                $this->stateManager->setState($symbol, $index, 'BOUGHT');
                $this->tradeRepository->logTrade($symbol, 'BUY', $tradeAmount, $currentPrice, $index);
                if ($this->notifier) {
                    $this->notifier->send("🟢 <b>COMPRA EJECUTADA</b>\nPar: {$symbol}\nNivel: {$index}\nPrecio: $" . number_format($currentPrice, 2) . "\nMonto: " . number_format($tradeAmount, 6));
                }
                continue; // Evita evaluar venta inmediatamente
            }

            // CRUCE HACIA ARRIBA (Venta capturando ganancia del spread)
            if ($state === 'BOUGHT' && $currentPrice >= ($levelPrice + $step)) {
                $this->orderRouter->routeOrder($symbol, 'SELL', $tradeAmount, $currentPrice);
                // Retornamos al estado inicial para que el nivel pueda volver a comprarse si el precio cae
                $this->stateManager->setState($symbol, $index, 'PENDING');
                
                // Cálculo realista de ganancia descontando comisiones del 0.1% (entrada y salida)
                $buyCost = ($levelPrice * $tradeAmount) * 1.001;
                $sellProceeds = ($currentPrice * $tradeAmount) * 0.999;
                $profit = $sellProceeds - $buyCost;
                
                $this->tradeRepository->logTrade($symbol, 'SELL', $tradeAmount, $currentPrice, $index, $profit);
                if ($this->notifier) {
                    $this->notifier->send("🔴 <b>VENTA EJECUTADA</b>\nPar: {$symbol}\nNivel: {$index}\nPrecio: $" . number_format($currentPrice, 2) . "\nGanancia: +$" . number_format($profit, 2) . " USDT");
                }
            }
        }
    }
}