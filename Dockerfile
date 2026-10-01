# Use PHP 8.4 FPM as the base image
FROM php:8.4-fpm

# Arguments defined in docker-compose.yml
ARG user=akartown
ARG uid=1000

# Install system dependencies and Node.js 22
RUN apt-get update && apt-get install -y \
    git \
    curl \
    gnupg \
    ca-certificates \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libicu-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (intl + zip + gd are required by Filament)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

# Fix permissions and ensure directories exist
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache \
    && chown -R $user:$user /var/www

# Switch to the non-root user
USER $user

# Copy only composer files first so this layer is cached unless dependencies change
COPY --chown=$user:$user composer.json composer.lock ./
RUN composer install --no-interaction --optimize-autoloader --no-scripts

# Copy only npm files first for the same reason
COPY --chown=$user:$user package.json package-lock.json ./
RUN npm ci

# Copy the rest of the application
COPY --chown=$user:$user . .

# Rebuild the autoloader now that Modules/*/composer.json exist (merge-plugin),
# which also runs the post-autoload-dump scripts
RUN composer dump-autoload --optimize --no-interaction

# Build frontend assets
RUN npm run build

# Copy entrypoint script
COPY --chown=$user:$user docker/entrypoint.sh /usr/local/bin/entrypoint.sh
USER root
RUN chmod +x /usr/local/bin/entrypoint.sh
USER $user

# Expose port 9000 and start entrypoint
EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
