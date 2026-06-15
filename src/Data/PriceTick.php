<?php

declare(strict_types=1);

namespace App\Data;

use JsonException;

readonly class PriceTick
{
    public function __construct(
        public string $symbol,
        public float $price,
        public int $timestamp
    ) {
    }

    public function toJson(): string
    {
        return json_encode(['symbol' => $this->symbol, 'price' => $this->price, 'timestamp' => $this->timestamp], JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return new self($data['symbol'], (float)$data['price'], (int)$data['timestamp']);
    }
}