# 🛡️ SafeGrid Crypto Trading Bot Framework

Un "Trading Bot Framework" modular, de alta frecuencia y tolerante a fallos, construido desde cero. 

**Propósito del Proyecto:** Este bot está diseñado bajo una filosofía financiera **conservadora**. Su objetivo principal no es la especulación de alto riesgo ("hacerse millonario"), sino la **protección del capital contra la devaluación** mediante la captura de ganancias en mercados de volatilidad lateral.

Actualmente opera de forma exclusiva en modo **100% Paper Trading** (simulación con dinero ficticio), permitiendo probar la viabilidad de las estrategias sin ningún riesgo financiero real.

---

## 🏗️ Arquitectura y Stack Tecnológico

El proyecto sigue estándares de calidad de software empresarial orientados al dominio financiero:

- **Lenguaje Core:** PHP 8.2+ configurado obligatoriamente en modo de tipado estricto (`declare(strict_types=1);` en todo el código fuente).
- **Runtime de Alto Rendimiento:** Utiliza FrankenPHP configurado en *Worker Mode*. Esto elimina el *overhead* tradicional de arranque de PHP, permitiendo que la aplicación resida en memoria para manejar eventos de mercado con concurrencia masiva y latencias de sub-milisegundos.
- **Integración de Exchanges:** Se implementa CCXT, lo que garantiza una estandarización de las APIs de cientos de exchanges globales bajo un mismo formato de respuesta.
- **Event Pipeline (Baja Latencia):** 
  - **Kafka:** Actuará como el *Event Bus* asíncrono para ingestar el *stream* masivo de precios (ticks) y emitir señales de trading.
  - **Redis:** Mantiene el estado en tiempo real (como los balances simulados y las posiciones abiertas) a una velocidad ultra-rápida.

---

## 🧩 Patrones de Diseño Core

La seguridad y la modularidad son los pilares del diseño de este framework. Para lograrlo, aplicamos el principio de **Inversión de Dependencias (Dependency Inversion)** y otros patrones fundamentales:

- **Aislamiento de la Ejecución (Strategy / Adapter):** 
  Toda la lógica de envío de órdenes o lectura de balances depende exclusivamente de la abstracción `OrderRouterInterface`. Esto permite inyectar el `PaperTradingSimulator` de forma transparente. El día de mañana, pasar a usar dinero real requerirá únicamente la inyección de una nueva clase que implemente la misma interfaz, sin alterar una sola línea de las estrategias o el core del bot.
- **Resiliencia de Red (Adapter):** 
  La clase `CcxtExchangeManager` actúa como un *Adapter* para la librería nativa de CCXT (implementando `ExchangeManagerInterface`). Su propósito es capturar de forma elegante las excepciones nativas (`NetworkError`, `RateLimitExceeded`) para transformarlas en excepciones manejables por nuestro sistema o dirigirlas hacia una futura cola de reintentos (*Dead Letter Queue*).

---

## 🗺️ Roadmap y Estado Actual

- ✅ **Fase 0 (Inicialización):** Setup integral de la estructura de directorios, *dockerización* avanzada (FrankenPHP, Redis, Kafka, Zookeeper) y configuración base de PHPUnit (TDD).
- ✅ **Fase 1 (Arquitectura Core):** Desarrollo de interfaces financieras y el motor de simulación de *Paper Trading* (`PaperTradingSimulator`) validado con tests unitarios.
- ✅ **Fase 2 (Flujo de Datos y Eventos):** Desarrollo de procesos para ingestar datos de mercado usando Kafka y almacenamiento ultra-rápido en Redis.
- ✅ **Fase 3 (Lógica de Estrategia):** Implementación de la estrategia *Spot Grid Trading* y DCA.
- ✅ **Fase 4 (Dashboard Visual):** Interfaz gráfica (Read-Only) en Laravel 11 + Livewire 3 + Tailwind CSS para monitorear Equity, ganancias netas y estado del Grid en tiempo real.
- ✅ **Fase 5 (Simulador de Estrés):** Script `FakeMarketFeeder` para simular volatilidad extrema y auditar la robustez del Bot bajo presión.
- ✅ **Fase 6 y 9 (WebSockets Ingestion):** Refactor de `BinanceMarketFeeder` para conectarse a Binance vía WebSockets, logrando alimentar a Kafka con ultra-baja latencia (milisegundos) y resiliencia de red (Exponential Backoff).
- ✅ **Fase 7 (Ledger Inmutable):** Persistencia histórica en base de datos PostgreSQL, inyectando un `PostgresTradeRepository` con auto-migración para el resguardo permanente de los trades.
- ✅ **Fase 8 (Notificaciones y Realismo):** Implementación de comisiones reales del 0.1% por transacción e integración de `TelegramNotifier` para alertas *push* al celular.

---

## 🚀 Instrucciones de Setup y Ejecución

El proyecto viene preconfigurado con Docker Compose para asegurar que todos los desarrolladores tengan exactamente el mismo entorno de ejecución.

1. **Clonar el repositorio y levantar la infraestructura:**
   ```bash
   docker-compose up -d --build
   ```
   *Esto descargará y compilará los contenedores de FrankenPHP, Redis, Kafka y Zookeeper.*

2. **Instalar dependencias mediante Composer:**
   Ejecuta el gestor de dependencias directamente dentro del contenedor de PHP:SS
   ```bash
   docker-compose exec php composer install
   ```

3. **Arrancar el Motor de Trading:**
   El framework está desacoplado, por lo que requiere mantener 2 terminales corriendo en paralelo.

   **Terminal 1 (El Listener / BotRunner):**
   Procesa Kafka, calcula la estrategia, guarda en BD y notifica.
   ```bash
   docker-compose exec php php src/Console/BotRunner.php
   ```

   **Terminal 2 (El Feeder / Datos de Mercado):**
   Inyecta datos a Kafka. Para el mercado real:
   ```bash
   docker-compose exec php php src/Console/BinanceMarketFeeder.php
   ```
   *(Opcional: Si quieres simular mercado loco, usa `src/Console/FakeMarketFeeder.php`)*

4. **Ver el Dashboard:**
   Si tienes instalada la interfaz en la subcarpeta `frontend/`:
   ```bash
   docker-compose exec php bash -c "cd frontend && php artisan serve --host=0.0.0.0 --port=8001"
   ```
   Abre en tu navegador: `http://localhost:8001`
   
   *Nota: Si necesitas resetear los balances, ejecuta `docker-compose exec php php src/Console/ResetState.php`.*

---

## 🧪 Testing y TDD

Toda la lógica financiera estricta debe ser probada usando metodologías TDD (*Test-Driven Development*). El framework utiliza PHPUnit 10.5.

Para ejecutar la suite de pruebas unitarias y verificar la integridad de módulos críticos como el `PaperTradingSimulator`:

```bash
docker-compose exec php ./vendor/bin/phpunit --testsuite Unit
```

Para correr la validación completa (Unit + Integration):

```bash
docker-compose exec php ./vendor/bin/phpunit
```