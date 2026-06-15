<?php

declare(strict_types=1);

namespace Tests\Unit\Trading;

use App\Repository\PriceRepositoryInterface;
use App\Trading\GridConfiguration;
use App\Trading\GridStateManagerInterface;
use App\Repository\TradeRepositoryInterface;
use App\Trading\OrderRouterInterface;
use App\Trading\SpotGridStrategy;
use PHPUnit\Framework\TestCase;

class SpotGridStrategyTest extends TestCase
{
    public function testZigZagMarketTriggersCorrectBuysAndSells(): void
    {
        // Configuración de la cuadrícula (100 a 150 con 5 grids = saltos de 10)
        // Niveles generados: 100, 110, 120, 130, 140, 150.
        $config = new GridConfiguration(150.0, 100.0, 5, 500.0);

        $priceRepo = $this->createMock(PriceRepositoryInterface::class);
        $orderRouter = $this->createMock(OrderRouterInterface::class);
        
        // Mock en memoria del StateManager (actuando como el Redis)
        $stateManager = new class implements GridStateManagerInterface {
            private array $states = [];
            public function getState(string $symbol, int $levelIndex): string {
                return $this->states["{$symbol}:{$levelIndex}"] ?? 'PENDING';
            }
            public function setState(string $symbol, int $levelIndex, string $state): void {
                $this->states["{$symbol}:{$levelIndex}"] = $state;
            }
        };

        $tradeRepo = $this->createMock(TradeRepositoryInterface::class);
        $strategy = new SpotGridStrategy($priceRepo, $orderRouter, $config, $stateManager, $tradeRepo);

        $callCount = 0;
        // Esperamos exactamente 5 órdenes (3 de compra, 2 de venta)
        $orderRouter->expects($this->exactly(5))
            ->method('routeOrder')
            ->willReturnCallback(function (string $symbol, string $side, float $amount, float $price) use (&$callCount) {
                $callCount++;
                if ($callCount <= 3) {
                    $this->assertSame('BUY', $side);
                    $this->assertSame(130.0, $price); // Las primeras 3 deben ser compras al caer el precio a 130
                } else {
                    $this->assertSame('SELL', $side);
                    $this->assertSame(150.0, $price); // Las siguientes 2 deben ser ventas al subir el precio a 150
                }
            });

        // TICK 1: Precio 160 (Mercado por encima del Grid, no hace nada)
        $priceRepo->method('getLastPrice')->willReturn(160.0);
        $strategy->execute('BTC/USDT');

        // TICK 2: Precio cae de golpe a 130 (Baja 3 escalones: 150, 140, 130)
        // Se deberían activar 3 órdenes de COMPRA.
        $priceRepo->method('getLastPrice')->willReturn(130.0);
        $strategy->execute('BTC/USDT');

        // TICK 3: Precio sube de golpe a 150 (Sube 2 escalones)
        // Las posiciones compradas en 130 y 140 alcanzan su target de venta (+10 de spread).
        // La posición en 150 aún requiere llegar a 160 para vender, por lo que se mantiene (BOUGHT).
        $priceRepo->method('getLastPrice')->willReturn(150.0);
        $strategy->execute('BTC/USDT');

        // Assert: Validamos que el estado del GRID es exactamente el esperado después de la tormenta
        // Nivel L130 (index 3) vendido -> vuelve a PENDING
        $this->assertSame('PENDING', $stateManager->getState('BTC/USDT', 3)); 
        
        // Nivel L140 (index 4) vendido -> vuelve a PENDING
        $this->assertSame('PENDING', $stateManager->getState('BTC/USDT', 4)); 
        
        // Nivel L150 (index 5) aún en HOLD -> se mantiene en BOUGHT esperando los 160
        $this->assertSame('BOUGHT', $stateManager->getState('BTC/USDT', 5));  
    }
}