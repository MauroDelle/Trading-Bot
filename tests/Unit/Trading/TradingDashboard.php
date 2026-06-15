<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Repository\PriceRepositoryInterface;
use App\Trading\GridConfiguration;
use App\Trading\GridStateManagerInterface;
use Livewire\Component;

class TradingDashboard extends Component
{
    public float $currentPrice = 0.0;
    public array $gridLevels = [];
    
    // Balances reales de la simulación
    public float $usdtBalance = 0.0;
    public float $btcBalance = 0.0;

    public function render(
        PriceRepositoryInterface $priceRepository,
        GridStateManagerInterface $stateManager
    ) {
        // Instanciamos Redis asumiendo que tu Laravel local puede resolver 127.0.0.1
        // Si estás usando sail, puedes cambiar '127.0.0.1' por la IP/Host correspondiente
        $redis = new \Predis\Client(['host' => '127.0.0.1', 'port' => 6379]);
        
        $this->usdtBalance = (float) ($redis->get('balance:USDT') ?? 100000.0);
        $this->btcBalance = (float) ($redis->get('balance:BTC') ?? 0.5);

        // Utilizamos la misma configuración de la estrategia
        // En un futuro, esto vendrá de una base de datos o archivo de configuración
        $config = new GridConfiguration(150.0, 100.0, 5, 500.0);
        
        $this->currentPrice = $priceRepository->getLastPrice('BTC/USDT') ?? 0.0;
        
        $levels = $config->getLevels();
        $this->gridLevels = [];
        
        // Consultamos el estado de cada nivel en Redis
        foreach ($levels as $index => $price) {
            $this->gridLevels[] = [
                'index' => $index,
                'price' => $price,
                'state' => $stateManager->getState('BTC/USDT', $index)
            ];
        }

        // Ordenamos para que los precios más altos (techo del grid) aparezcan arriba visualmente
        usort($this->gridLevels, fn($a, $b) => $b['price'] <=> $a['price']);

        return view('livewire.trading-dashboard')->layout('components.layouts.app');
    }
}