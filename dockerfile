# Usamos PHP 8.2 con Apache
FROM php:8.2-apache

# Instalamos dependencias necesarias para PHP y Apache
RUN apt-get update && apt-get install -y \
    build-essential \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    git \
    curl \
    cron \
    lsb-release \
    gnupg2 \
    && rm -rf /var/lib/apt/lists/*

# Instalamos Node.js 18 (LTS) y npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Instalamos las extensiones de PHP necesarias
RUN docker-php-ext-install pdo_mysql zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Habilitamos mod_rewrite para Laravel
RUN a2enmod rewrite

# Instalamos Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiamos solo los archivos de Composer primero (para cache)
COPY composer*.json /var/www/

# Nos movemos al directorio /var/www
WORKDIR /var/www/

# Instalamos las dependencias de Composer
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

# Copiamos solo los archivos de npm (package.json y package-lock.json) primero para optimizar el caching de npm
COPY package*.json /var/www/

# Instalamos las dependencias de npm (solo si los archivos package.json han cambiado)
RUN npm install

# Copiamos todo el código del proyecto
COPY . /var/www/

# Aseguramos que apache pueda acceder a los archivos
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Ejecutamos npm run build para generar el archivo manifest.json
RUN npm run build

# Generamos el archivo .env con las credenciales de la base de datos
RUN echo "DB_CONNECTION=mysql" > /var/www/.env && \
    echo "DB_HOST=mysql-db" >> /var/www/.env && \
    echo "DB_PORT=3306" >> /var/www/.env && \
    echo "DB_DATABASE=administracion_suscripciones" >> /var/www/.env && \
    echo "DB_USERNAME=root" >> /var/www/.env && \
    echo "DB_PASSWORD=Hol@$2044" >> /var/www/.env

# Configuramos el directorio de trabajo y el DocumentRoot de Apache
WORKDIR /var/www/public

# Configuramos cron para Laravel
RUN echo "* * * * * /usr/local/bin/php /var/www/artisan schedule:run >> /var/www/storage/logs/cron.log 2>&1" > /etc/cron.d/laravel-schedule \
    && chmod 0644 /etc/cron.d/laravel-schedule \
    && crontab /etc/cron.d/laravel-schedule

# Exponemos el puerto 80 para Apache
EXPOSE 80

# Corremos Apache y Cron en primer plano
CMD ["sh", "-c", "cron && apache2-foreground"]
