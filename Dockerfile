FROM php:8.2-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar solo composer.json y composer.lock primero
COPY composer.json composer.lock ./

# Instalar dependencias de PHP
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Copiar el resto de los archivos del proyecto
COPY . .

# Configurar permisos para los logs
RUN mkdir -p logs && chmod -R 777 logs

# Exponer puerto
EXPOSE 8080

# Comando por defecto
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
