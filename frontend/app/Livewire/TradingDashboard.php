<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Predis\Client;

class TradingDashboard extends Component
{
    public float $currentPrice = 0.0;
    public array $gridLevels = [];
    public float $usdtBalance = 0.0;
    public float $btcBalance = 0.0;
    public array $latestTrades = [];
    
    public float $initialInvestment = 400.0;
    public float $totalEquity = 0.0;
    public float $profitUsdt = 0.0;

    public function render()
    {
        $redis = new Client(['host' => 'redis', 'port' => 6379]);
        
        $this->usdtBalance = (float) ($redis->get('balance:USDT') ?? 400.0);
        $this->btcBalance = (float) ($redis->get('balance:BTC') ?? 0.0);
        $this->currentPrice = (float) ($redis->get('price:BTC/USDT') ?? 0.0);

        // Cálculos amigables de rendimiento
        $this->totalEquity = $this->usdtBalance + ($this->btcBalance * $this->currentPrice);
        $this->profitUsdt = $this->totalEquity - $this->initialInvestment;

        // Reconstruimos visualmente los niveles reales (60k a 70k)
        $upperPrice = 70000.0;
        $lowerPrice = 60000.0;
        $totalGrids = 10;
        $step = ($upperPrice - $lowerPrice) / $totalGrids;
        
        $levels = [];
        for ($i = 0; $i <= $totalGrids; $i++) {
            $levels[] = $lowerPrice + ($step * $i);
        }
        $this->gridLevels = [];
        
        foreach ($levels as $index => $price) {
            $this->gridLevels[] = [
                'index' => $index,
                'price' => $price,
                'state' => $redis->get("grid:state:BTC/USDT:{$index}") ?? 'PENDING'
            ];
        }

        usort($this->gridLevels, fn($a, $b) => $b['price'] <=> $a['price']);

        try {
            $pdo = new \PDO('pgsql:host=postgres;port=5432;dbname=tradingbot', 'botuser', 'botpassword', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
            $stmt = $pdo->query("SELECT * FROM trade_history ORDER BY created_at DESC LIMIT 10");
            $this->latestTrades = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Si PostgreSQL no está levantado, evitamos que el panel colapse
            $this->latestTrades = [];
        }

        return view('livewire.trading-dashboard')->layout('components.layouts.app');
    }
}