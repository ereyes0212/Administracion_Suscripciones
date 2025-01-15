# Usa una imagen base de PHP con Apache
FROM php:8.2-apache

# Instala dependencias necesarias y extiende PHP
RUN apt-get update && apt-get install -y \
    cron \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmariadb-dev-compat \
    libmariadb-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*  # Limpia la caché de apt para reducir el tamaño de la imagen

# Configura la zona horaria a la hora local deseada
RUN ln -sf /usr/share/zoneinfo/America/Mexico_City /etc/localtime && dpkg-reconfigure -f noninteractive tzdata

# Habilita mod_rewrite en Apache
RUN a2enmod rewrite

# Configura Apache para que apunte al directorio public de Laravel
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf \
    && echo '    DocumentRoot /var/www/html/public' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    <Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '        Options Indexes FollowSymLinks' >> /etc/apache2/sites-available/000-default.conf \
    && echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf \
    && echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia solo los archivos necesarios para Composer y luego instala las dependencias
COPY composer.json composer.lock /var/www/html/

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala dependencias de Laravel
RUN composer install --no-dev --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist

# Copia el resto del código del proyecto
COPY . /var/www/html/

# Establece los permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Crear cronjob para el comando de Laravel Schedule sin especificar usuario
RUN echo "* * * * * /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/www/html/storage/logs/cron.log 2>&1" > /etc/cron.d/laravel-schedule \
    && chmod 0644 /etc/cron.d/laravel-schedule \
    && crontab /etc/cron.d/laravel-schedule # Registra el cronjob sin usuario específico

# Asegura que el cron esté habilitado y en ejecución
RUN service cron start

# Exponer el puerto 80 de Apache
EXPOSE 80

# Inicia cron y Apache
CMD ["bash", "-c", "cron && apache2-foreground"]
