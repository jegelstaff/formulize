FROM php:8.1-apache
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev
RUN docker-php-ext-configure gd --with-jpeg=/usr/include/
RUN docker-php-ext-install mysqli pdo pdo_mysql gd
RUN pecl install xdebug
RUN docker-php-ext-enable mysqli pdo pdo_mysql gd xdebug
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"