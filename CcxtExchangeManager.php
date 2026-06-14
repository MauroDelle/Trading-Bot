<?php

declare(strict_types=1);

namespace App\Exchange;

use ccxt\Exchange;
use ccxt\NetworkError;
use ccxt\RateLimitExceeded;
use Exception;
use RuntimeException;

class CcxtExchangeManager implements ExchangeManagerInterface
{
    public function __construct(
        private Exchange $exchange
    ) {
    }

    public function fetchTicker(string $symbol): array
    {
        try {
            return $this->exchange->fetch_ticker($symbol);
        } catch (RateLimitExceeded $e) {
            // Podríamos enviar a una cola de reintentos (Dead Letter Queue) o aplicar backoff
            throw new RuntimeException("Rate limit excedido en el exchange para {$symbol}: " . $e->getMessage(), 0, $e);
        } catch (NetworkError $e) {
            // Captura timeouts o desconexiones del exchange
            throw new RuntimeException("Error de red al conectar con el exchange para {$symbol}: " . $e->getMessage(), 0, $e);
        } catch (Exception $e) {
            // Fallo general (ej. par no soportado, error interno de CCXT)
            throw new RuntimeException("Error inesperado obteniendo el ticker de {$symbol}: " . $e->getMessage(), 0, $e);
        }
    }
}