<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class PostgresTradeRepository implements TradeRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
        // Auto-crear la tabla si no existe para facilitar el entorno local
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS trade_history (
            id SERIAL PRIMARY KEY,
            symbol VARCHAR(20) NOT NULL,
            side VARCHAR(4) NOT NULL,
            amount DECIMAL(18, 8) NOT NULL,
            price DECIMAL(18, 8) NOT NULL,
            grid_level INT NOT NULL,
            realized_profit DECIMAL(18, 8) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function logTrade(string $symbol, string $side, float $amount, float $price, int $gridLevel, ?float $realizedProfit = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO trade_history (symbol, side, amount, price, grid_level, realized_profit) 
             VALUES (:symbol, :side, :amount, :price, :grid_level, :realized_profit)'
        );
        
        $stmt->execute([
            ':symbol' => $symbol, ':side' => $side, ':amount' => $amount, 
            ':price' => $price, ':grid_level' => $gridLevel, ':realized_profit' => $realizedProfit
        ]);
    }
}