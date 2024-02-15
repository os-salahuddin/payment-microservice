FROM yiisoftware/yii2-php:7.3-apache

RUN a2enmod rewrite

WORKDIR /app

COPY . .

RUN composer install --ignore-platform-reqs --no-interaction --prefer-dist --optimize-autoloader && \
    composer clear-cache

RUN mkdir -p runtime web/assets && \
    chmod -R 775 runtime web/assets && \
    chmod -R 775 runtime web/uploads && \
    chown -R www-data:www-data runtime web/assets

EXPOSE 80
