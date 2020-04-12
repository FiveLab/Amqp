FROM php:7.2-cli

MAINTAINER Vitalii Zhuk <v.zhuk@fivelab.org>

ARG XDEBUG_REMOTE_HOST='host.docker.internal'
ARG XDEBUG_REMOTE_PORT=9000

ENV PHP_IDE_CONFIG='serverName=amqp.local'

RUN \
    apt-get update && \
    apt-get install -y --no-install-recommends \
        git ssh-client \
        zip unzip

# Install additional php extensions
RUN \
    apt-get install -y --no-install-recommends \
        librabbitmq-dev && \
    printf '\n' | pecl install amqp && \
    yes | pecl install xdebug && \
    docker-php-ext-enable amqp xdebug

# Configure XDebug
RUN \
    echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_autostart=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_connect_back=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_host=${XDEBUG_REMOTE_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_port=${XDEBUG_REMOTE_PORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.max_nesting_level=1500" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

WORKDIR /code
