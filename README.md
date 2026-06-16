# 🛡️ SafeGrid Crypto Trading Bot Framework

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777bb4.svg?style=flat-square&logo=php)
![Docker](https://img.shields.io/badge/Docker-Enabled-2496ed.svg?style=flat-square&logo=docker)
![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)
![Status](https://img.shields.io/badge/Status-Active_Development-brightgreen.svg?style=flat-square)

Un framework modular, de alta frecuencia y tolerante a fallos para el trading automatizado de criptomonedas, construido con estándares de ingeniería de software empresarial.

**Filosofía del Proyecto:** Este bot está diseñado bajo un enfoque financiero **conservador**. Su objetivo principal no es la especulación de alto riesgo, sino la **preservación del capital frente a la devaluación** mediante la captura sistemática de ganancias en mercados laterales y volátiles utilizando la estrategia de *Grid Trading* y *DCA*.

Actualmente, el sistema opera exclusivamente en modo **100% Paper Trading** (simulación con capital ficticio), permitiendo a los usuarios realizar *backtesting* y pruebas en vivo sin riesgo financiero antes de operar con dinero real.

---

## ✨ Características Principales

- **Estrategia Spot Grid:** Ejecución automatizada de compras en rangos bajos y ventas en rangos altos dentro de una cuadrícula de precios definida.
- **Motor de Paper Trading:** Entorno de ejecución simulado realista que incluye el cálculo de comisiones estándar de mercado (0.1% maker/taker).
- **Pipeline de Eventos de Alta Frecuencia:** Ingesta de datos del mercado vía Kafka combinada con Redis para la gestión del estado con latencias de sub-milisegundos.
- **Ledger Inmutable de Operaciones:** Persistencia histórica en PostgreSQL para auditar y registrar permanentemente todas las operaciones ejecutadas.
- **Dashboard en Tiempo Real:** Panel de control desarrollado con Laravel 11, Livewire 3 y Tailwind CSS para monitorear el *Equity*, ganancias netas y el estado del Grid.
- **Notificaciones Push:** Integración nativa con Telegram para recibir alertas inmediatas sobre ejecuciones de órdenes y estado del bot.
- **Agnóstico de Exchanges:** Preparado para la integración mediante CCXT, facilitando la futura conexión con cientos de exchanges a nivel mundial.

---

## 🏗️ Arquitectura y Stack Tecnológico

El proyecto sigue principios de Diseño Guiado por el Dominio (DDD) y patrones SOLID, garantizando modularidad y facilidad de prueba.

- **Lenguaje Core:** PHP 8.2+ con tipado estricto (`declare(strict_types=1);`).
- **Entorno de Ejecución:** FrankenPHP en *Worker Mode*, eliminando el *overhead* de inicialización de PHP y permitiendo la ejecución persistente en memoria.
- **Message Broker:** Apache Kafka y Zookeeper para un flujo de eventos de mercado asíncrono y desacoplado.
- **Almacenamiento en Memoria:** Redis para lectura/escritura ultrarrápida del estado de los grids, últimos precios y balances simulados.
- **Base de Datos Relacional:** PostgreSQL para el almacenamiento persistente e inmutable del historial de *trades*.
- **Frontend:** Laravel Livewire para una experiencia reactiva tipo SPA (Single Page Application).

### Patrones de Diseño Centrales
- **Inversión de Dependencias (Dependency Inversion):** El uso de interfaces como `OrderRouterInterface` permite intercambiar sin fricción entre el simulador (`PaperTradingSimulator`) y futuros adaptadores de exchanges reales, sin modificar la lógica de la estrategia.
- **Patrón Adapter:** Envoltura de dependencias externas (como CCXT) para estandarizar el manejo de errores (`NetworkError`, `RateLimitExceeded`) y enrutarlos, en el futuro, hacia colas de mensajes fallidos (*Dead Letter Queues*).

---

## 🚀 Guía de Inicio Rápido

### Requisitos Previos
- Docker y Docker Compose (v2+)
- Git

### 1. Instalación e Infraestructura

Clona el repositorio y levanta la infraestructura contenerizada (FrankenPHP, Redis, Kafka, Zookeeper, PostgreSQL):

```bash
git clone https://github.com/tu-usuario/trading-bot.git
cd trading-bot
docker-compose up -d --build
```

Instala las dependencias del backend usando Composer dentro del contenedor de PHP:
```bash
docker-compose exec php composer install
```

### 2. Configuración

Antes de ejecutar el bot, revisa y ajusta las configuraciones:
- **Estrategias:** Define tus pares de trading, rangos de precios, niveles del grid y asignación de capital en el archivo `config/strategies.php`.
- **Notificaciones:** (Opcional) Para activar las alertas de Telegram, configura tu Token de Bot y Chat ID en `src/Console/BotRunner.php`.

### 3. Ejecución del Bot de Trading

El sistema está desacoplado mediante micro-servicios, por lo que necesitarás ejecutar procesos paralelos en terminales distintas.

**Terminal 1: Ingesta de Datos del Mercado (Feeder)**
Inyecta datos en tiempo real al flujo de Kafka.
```bash
# Para datos reales vía WebSockets (ej. Binance):
docker-compose exec php php src/Console/BinanceMarketFeeder.php

# Opcional, para simular estrés y alta volatilidad artificial:
docker-compose exec php php src/Console/FakeMarketFeeder.php
```

**Terminal 2: Motor Central (Worker / BotRunner)**
Consume los eventos de Kafka, evalúa la estrategia, envía órdenes y guarda los estados.
```bash
docker-compose exec php php src/Console/BotRunner.php
```

### 4. Monitoreo en Tiempo Real (Dashboard)

Para levantar el panel visual, inicia el servidor de Laravel en el directorio `frontend`:
```bash
docker-compose exec php bash -c "cd frontend && php artisan serve --host=0.0.0.0 --port=8001"
```
Accede desde tu navegador web: http://localhost:8001

*(Nota: Si deseas reiniciar los balances simulados y estados del grid a sus valores por defecto, ejecuta `docker-compose exec php php src/Console/ResetState.php`)*

---

## 🧪 Pruebas y Aseguramiento de Calidad (TDD)

El manejo de lógicas financieras requiere validaciones rigurosas. El framework emplea PHPUnit y metodologías orientadas a pruebas (TDD).

Para ejecutar las pruebas unitarias (validando algoritmos de la estrategia matemática aislada):
```bash
docker-compose exec php ./vendor/bin/phpunit --testsuite Unit
```

Para correr la suite de pruebas completa (Unitaria + Integración):
```bash
docker-compose exec php ./vendor/bin/phpunit
```

---

## 🗺️ Roadmap de Desarrollo

- [x] **Fase 1-3:** Arquitectura Core, Pipeline Kafka/Redis, Simulador de Paper Trading, Lógica Spot Grid.
- [x] **Fase 4-5:** Dashboard con Livewire, Simulador de Estrés Artificial (Fake Market).
- [x] **Fase 6:** Ingesta de WebSockets con ultra-baja latencia (Binance).
- [x] **Fase 7-8:** Ledger inmutable en PostgreSQL, Comisiones realistas (0.1%), Alertas en Telegram.
- [ ] **Fase 9:** *Dynamic Grid Spacing* (Cuadrícula adaptable basada en volatilidad ATR).
- [ ] **Fase 10:** Integración de *Live Trading* mediante APIs de Exchanges reales (CCXT).
- [ ] **Fase 11:** *Trailing Grid* (Límites de la cuadrícula dinámicos ante subidas/bajadas estructurales del precio).

---

## ⚠️ Aviso Legal (Disclaimer)

**Este software se proporciona con fines educativos y experimentales.**
El trading de criptomonedas conlleva un alto nivel de riesgo y puede no ser adecuado para todos los inversores. Los desarrolladores y contribuyentes de este framework no se hacen responsables por ninguna pérdida financiera en la que se incurra mediante el uso de este software, ya sea en entornos simulados o en operaciones reales. Utilícelo bajo su propia responsabilidad.

```bash
docker-compose exec php ./vendor/bin/phpunit --testsuite Unit
```

Para correr la validación completa (Unit + Integration):

```bash
docker-compose exec php ./vendor/bin/phpunit
```