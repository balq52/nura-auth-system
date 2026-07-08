FROM php:8.2-apache
RUN ls -la /etc/apache2/mods-enabled/ | grep mpm

RUN apt-get update && apt-get install -y \
    libssl-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mysqli zip

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN grep -rn "LoadModule mpm_" /etc/apache2/ || true
RUN grep -rl "LoadModule mpm_event_module\|LoadModule mpm_worker_module" /etc/apache2/ | xargs -r sed -i '/LoadModule mpm_event_module\|LoadModule mpm_worker_module/d' || true
RUN a2enmod mpm_prefork 2>&1 || true
RUN a2enmod rewrite
RUN grep -rn "LoadModule mpm_" /etc/apache2/ || true

WORKDIR /var/www/html

COPY . /var/www/html/

RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-mongodb || composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
