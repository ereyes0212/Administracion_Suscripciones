FROM php:8.2-apache

# Instala dependencias necesarias y extiende PHP
RUN apt-get update && apt-get install -y \
    cron \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmariadb-dev-compat \
    libmariadb-dev \
    pkg-config \
    npm \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer --version

# Copia el código del proyecto
COPY . /var/www/html/

# Instala dependencias de npm y construye los archivos de Vite
RUN npm install && npm run build

# Configura la zona horaria
RUN ln -sf /usr/share/zoneinfo/America/Mexico_City /etc/localtime && dpkg-reconfigure -f noninteractive tzdata

# Habilita mod_rewrite en Apache
RUN a2enmod rewrite

# Copia la configuración de Apache
COPY ./apache-config.conf /etc/apache2/sites-available/000-default.conf

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Instala dependencias de Composer
RUN composer install --no-dev --prefer-dist --no-scripts --no-plugins --no-interaction

# Copia el archivo .env.example
COPY .env.example /var/www/html/.env

# Reemplaza las variables de la base de datos en el archivo .env
RUN sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=mysql/' /var/www/html/.env \
    && sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/' /var/www/html/.env \
    && sed -i 's/DB_PORT=3306/DB_PORT=3306/' /var/www/html/.env \
    && sed -i 's/DB_DATABASE=homestead/DB_DATABASE=administracion_suscripciones/' /var/www/html/.env \
    && sed -i 's/DB_USERNAME=root/DB_USERNAME=root/' /var/www/html/.env \
    && sed -i 's/DB_PASSWORD=secret/DB_PASSWORD=Hol@$$2044/' /var/www/html/.env

# Crea directorios y asigna permisos
RUN mkdir -p /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/framework/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/storage/framework /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/storage/framework /var/www/html/bootstrap/cache

# Genera la clave de Laravel
RUN php artisan key:generate

# Ejecuta migraciones (solo una vez)
RUN php artisan migrate --force

# Crear cronjob para Laravel Scheduler
RUN echo "* * * * * /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/www/html/storage/logs/cron.log 2>&1" > /etc/cron.d/laravel-schedule \
    && chmod 0644 /etc/cron.d/laravel-schedule \
    && crontab /etc/cron.d/laravel-schedule

# Exponer puerto 80
EXPOSE 80

# Usar supervisord para iniciar cron y Apache
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
