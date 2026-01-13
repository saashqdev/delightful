ARG IMAGE_NAME=ghcr.io/saashqdev/php-dockerfile:8.4-alpine-3.22-swow-1.6.1-jsonpath-parle-xlswriter

FROM --platform=$BUILDPLATFORM ${IMAGE_NAME}

ARG timezone
ARG TARGETPLATFORM

ENV TIMEZONE=${timezone:-"America/Toronto"} \
    SCAN_CACHEABLE=(true) \
    USE_ZEND_ALLOC=0 \
    COMPOSER_FUND=0 \
    PHP_MEMORY_LIMIT=-1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    PHP_INI_MEMORY_LIMIT=-1

# Configure PHP settings
RUN mkdir -p /etc/php/conf.d && \
    echo "memory_limit = -1" > /etc/php/conf.d/memory-limit.ini && \
    echo "max_execution_time = 0" > /etc/php/conf.d/max-execution-time.ini
    

COPY . /opt/www

WORKDIR /opt/www


# Optional: switch composer to the Aliyun mirror
# RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/


# Disable the swow extension before installing, because after enabling swow a composer update causes curl to loop
RUN  php -d swow.enable=0  $(which composer) update 

# Optional: mark exposed ports
EXPOSE 9501
EXPOSE 9502

ENTRYPOINT ["sh", "/opt/www/start.sh"]