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
    bash

# Install Redis & rdkafka extensions via PECL
RUN pecl install redis rdkafka \
    && docker-php-ext-enable redis rdkafka

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Default command (can be overridden to run workers/consumers)
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
