FROM dunglas/frankenphp:php8.2-alpine

# Set working directory
WORKDIR /app

# Install development dependencies and extensions
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    librdkafka-dev \
    linux-headers \
    git \
    unzip \
    bash \
    gmp-dev \
    postgresql-dev

# Install bcmath, gmp, pcntl, and pdo_pgsql core extensions
RUN docker-php-ext-install bcmath gmp pdo_pgsql pcntl

# Install Redis & rdkafka extensions via PECL
RUN pecl install redis rdkafka \
    && docker-php-ext-enable redis rdkafka

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Default command (can be overridden to run workers/consumers)
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
