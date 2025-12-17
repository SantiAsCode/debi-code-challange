FROM php:8.4-cli-alpine

ARG WWWGROUP=1000
ARG NODE_VERSION=22
ARG POSTGRES_VERSION=17

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_COMMAND="/usr/local/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

# Install dependencies and extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_pgsql bcmath intl zip opcache pcntl gd

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    supervisor \
    nodejs \
    npm \
    postgresql-client \
    shadow \
    libcap \
    bash \
    gosu

RUN mkdir -p /var/log/supervisor

# Allow non-root PHP to bind to port 80
RUN setcap "cap_net_bind_service=+ep" /usr/local/bin/php

# Configure User
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

# Copy configuration
COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /usr/local/etc/php/conf.d/99-sail.ini

RUN chmod +x /usr/local/bin/start-container

EXPOSE 80

ENTRYPOINT ["start-container"]
