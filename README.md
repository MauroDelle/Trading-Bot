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
- ✅ **Fase 1 (Arquitectura Core):** Desarrollo de interfaces financieras, patrón Adapter para CCXT (`CcxtExchangeManager`) y el motor de simulación de *Paper Trading* (`PaperTradingSimulator`) validado con tests unitarios.
- ⏳ **Fase 2 (Flujo de Datos y Eventos):** Desarrollo de procesos productores/consumidores para ingestar datos de mercado mediante Apache Kafka y almacenar el estado en Redis.
- ⏳ **Fase 3 (Lógica de Estrategia):** Implementación de la estrategia *Spot Grid Trading* y DCA.

---

## 🚀 Instrucciones de Setup Local

El proyecto viene preconfigurado con Docker Compose para asegurar que todos los desarrolladores tengan exactamente el mismo entorno de ejecución.

1. **Clonar el repositorio y levantar la infraestructura:**
   ```bash
   docker-compose up -d
   ```
   *Esto descargará y compilará los contenedores de FrankenPHP, Redis, Kafka y Zookeeper.*

2. **Instalar dependencias mediante Composer:**
   Ejecuta el gestor de dependencias directamente dentro del contenedor de PHP:SS
   ```bash
   docker-compose exec php composer install
   ```

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