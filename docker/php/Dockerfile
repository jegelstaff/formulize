FROM php:8.1-apache
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libzip-dev zip libfreetype6-dev
RUN docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-freetype=/usr/include/
RUN docker-php-ext-install mysqli pdo pdo_mysql gd zip
RUN pecl install xdebug
RUN docker-php-ext-enable mysqli pdo pdo_mysql gd xdebug zip
RUN a2enmod rewrite
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
