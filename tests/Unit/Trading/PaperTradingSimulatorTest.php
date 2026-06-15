<?php

declare(strict_types=1);

namespace Tests\Unit\Trading;

use App\Exchange\ExchangeManagerInterface;
use App\Trading\PaperTradingSimulator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PaperTradingSimulatorTest extends TestCase
{
    private ExchangeManagerInterface $exchangeMock;

    protected function setUp(): void
    {
        // Creamos el mock de la interfaz del Exchange para aislar las pruebas de la red
        $this->exchangeMock = $this->createMock(ExchangeManagerInterface::class);
    }

    public function testSuccessfulMarketBuyOrderUpdatesBalance(): void
    {
        // Simulamos que el precio de BTC es 50,000 USDT
        $this->exchangeMock->method('fetchTicker')
            ->with('BTC/USDT')
            ->willReturn(['last' => 50000.0]);

        // Balance inicial: 100,000 USDT, 0 BTC
        $simulator = new PaperTradingSimulator($this->exchangeMock, ['USDT' => 100000.0, 'BTC' => 0.0]);
        
        // Ejecutamos compra de 1 BTC
        $order = $simulator->placeMarketBuyOrder('BTC/USDT', 1.0);

        $this->assertEquals(50000.0, $simulator->getBalance('USDT'));
        $this->assertEquals(1.0, $simulator->getBalance('BTC'));
        $this->assertEquals('closed', $order['status']);
        $this->assertEquals(50000.0, $order['cost']);
    }

    public function testSuccessfulMarketSellOrderUpdatesBalance(): void
    {
        $this->exchangeMock->method('fetchTicker')
            ->with('BTC/USDT')
            ->willReturn(['last' => 50000.0]);

        // Balance inicial: 0 USDT, 2 BTC
        $simulator = new PaperTradingSimulator($this->exchangeMock, ['USDT' => 0.0, 'BTC' => 2.0]);
        
        // Ejecutamos venta de 1 BTC
        $simulator->placeMarketSellOrder('BTC/USDT', 1.0);

        $this->assertEquals(50000.0, $simulator->getBalance('USDT'));
        $this->assertEquals(1.0, $simulator->getBalance('BTC'));
    }

    public function testInsufficientBalanceThrowsException(): void
    {
        $this->exchangeMock->method('fetchTicker')
            ->willReturn(['last' => 50000.0]);

        $simulator = new PaperTradingSimulator($this->exchangeMock, ['USDT' => 10000.0]); // Solo 10k USDT
        
        $this->expectException(RuntimeException::class);
        $simulator->placeMarketBuyOrder('BTC/USDT', 1.0); // Requiere 50k
    }
}