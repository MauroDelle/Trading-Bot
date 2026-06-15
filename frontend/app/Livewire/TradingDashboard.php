<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Predis\Client;

class TradingDashboard extends Component
{
    public array $gridLevels = [];
    public float $usdtBalance = 0.0;
    public array $latestTrades = [];
    
    public float $initialInvestment = 0.0;
    public float $totalEquity = 0.0;
    public float $profitUsdt = 0.0;
    
    public array $balances = [];
    public array $prices = [];
    public string $activeTab = 'BTC/USDT';

    public function render()
    {
        $redis = new Client(['host' => 'redis', 'port' => 6379]);
        $configs = require __DIR__ . '/../../../config/strategies.php';
        
        $this->usdtBalance = (float) ($redis->get('balance:USDT') ?? 0.0);
        $this->initialInvestment = 0.0;
        $this->totalEquity = $this->usdtBalance;
        
        $this->balances = [];
        $this->prices = [];

        foreach ($configs as $symbol => $c) {
            $this->initialInvestment += $c['totalInvestment'];
            
            list($base, $quote) = explode('/', $symbol);
            $balance = (float) ($redis->get("balance:{$base}") ?? 0.0);
            $price = (float) ($redis->get("price:{$symbol}") ?? 0.0);
            
            $this->balances[$symbol] = $balance;
            $this->prices[$symbol] = $price;
            
            $this->totalEquity += ($balance * $price);
        }
        
        $this->profitUsdt = $this->totalEquity - $this->initialInvestment;

        $this->gridLevels = [];
        $activeConfig = $configs[$this->activeTab] ?? null;
        
        if ($activeConfig) {
            $step = ($activeConfig['upperPrice'] - $activeConfig['lowerPrice']) / $activeConfig['totalGrids'];
            for ($i = 0; $i <= $activeConfig['totalGrids']; $i++) {
                $p = $activeConfig['lowerPrice'] + ($step * $i);
                $this->gridLevels[] = ['index' => $i, 'price' => $p, 'state' => $redis->get("grid:state:{$this->activeTab}:{$i}") ?? 'PENDING'];
            }
            usort($this->gridLevels, fn($a, $b) => $b['price'] <=> $a['price']);
        }

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